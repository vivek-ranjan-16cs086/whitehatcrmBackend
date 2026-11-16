<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeviceRegistrationService;
use App\Services\FirebaseNotificationService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Mockery;
use Tests\TestCase;

class UserDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_refresh_and_account_switch_preserve_other_devices(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user, 'api');
        $this->postJson('/api/v1/device/register', ['device_id' => 'phone', 'platform' => 'android', 'fcm_token' => 'old'])->assertOk();
        $this->postJson('/api/v1/device/register', ['device_id' => 'browser', 'platform' => 'web', 'fcm_token' => 'web'])->assertOk();
        $this->postJson('/api/v1/device/register', ['device_id' => 'phone', 'platform' => 'android', 'fcm_token' => 'new'])->assertOk();
        $this->assertDatabaseCount('user_devices', 2);
        $this->assertDatabaseMissing('user_devices', ['fcm_token' => 'old']);
        $this->actingAs($other, 'api')->postJson('/api/v1/device/register', ['device_id' => 'phone', 'platform' => 'android', 'fcm_token' => 'new'])->assertOk();
        $this->assertDatabaseCount('user_devices', 2);
        $this->assertDatabaseHas('user_devices', ['user_id' => $other->id, 'fcm_token' => 'new']);
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id, 'fcm_token' => 'web']);
    }

    public function test_unregister_only_removes_the_authenticated_users_selected_device(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $devices = app(DeviceRegistrationService::class);
        $devices->register($user, 'phone', 'android', 'phone-token');
        $devices->register($user, 'browser', 'web', 'web-token');
        $devices->register($other, 'phone', 'android', 'other-token');
        $this->actingAs($user, 'api')->postJson('/api/v1/device/unregister', ['device_id' => 'phone'])->assertOk();
        $this->assertDatabaseCount('user_devices', 2);
        $this->assertDatabaseHas('user_devices', ['user_id' => $other->id, 'device_id' => 'phone']);
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id, 'device_id' => 'browser']);
    }

    public function test_registration_requires_authentication_and_valid_input(): void
    {
        $this->postJson('/api/v1/device/register', [])->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'api')->postJson('/api/v1/device/register', [])->assertUnprocessable();
        $this->postJson('/api/v1/store-device-token', ['deviceToken' => str_repeat('x', 4097)])->assertUnprocessable();
    }

    public function test_legacy_endpoint_adds_devices_without_writing_users_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        foreach (['one', 'two', 'one'] as $token) {
            $this->postJson('/api/v1/store-device-token', ['deviceToken' => $token])->assertOk();
        }
        $this->assertDatabaseCount('user_devices', 2);
        $this->assertNull($user->fresh()->device_token);
    }

    public function test_push_failure_does_not_block_other_devices_or_duplicate_history(): void
    {
        $user = User::factory()->create();
        $devices = app(DeviceRegistrationService::class);
        $devices->register($user, 'phone', 'android', 'bad');
        $devices->register($user, 'browser', 'web', 'good');
        $firebase = Mockery::mock(FirebaseNotificationService::class)->makePartial();
        $firebase->shouldReceive('sendToDevice')->once()->with('bad', 'New Lead Assigned', Mockery::any(), Mockery::any())
            ->andThrow(new \RuntimeException('Temporary failure'));
        $firebase->shouldReceive('sendToDevice')->once()->with('good', 'New Lead Assigned', Mockery::any(), Mockery::any())->andReturn([]);
        (new NotificationService($firebase))->sendLeadAssignedNotification($user->id, (object) ['id' => 1, 'name' => 'Lead', 'source_website' => 'web']);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('user_devices', 2);
    }

    public function test_only_confirmed_unregistered_tokens_are_deleted(): void
    {
        $user = User::factory()->create();
        $devices = app(DeviceRegistrationService::class);
        $devices->register($user, 'phone', 'android', 'expired');
        $devices->register($user, 'browser', 'web', 'wrong-project');
        $firebase = Mockery::mock(FirebaseNotificationService::class)->makePartial();
        $firebase->shouldReceive('sendToDevice')->once()->with('expired', 'Title', 'Body', [])
            ->andThrow(NotFound::becauseTokenNotFound('expired', ['error' => ['details' => [['errorCode' => 'UNREGISTERED']]]]));
        $firebase->shouldReceive('sendToDevice')->once()->with('wrong-project', 'Title', 'Body', [])
            ->andThrow(NotFound::becauseTokenNotFound('wrong-project'));
        $firebase->sendToUser($user, 'Title', 'Body');
        $this->assertDatabaseMissing('user_devices', ['fcm_token' => 'expired']);
        $this->assertDatabaseHas('user_devices', ['fcm_token' => 'wrong-project']);
    }

    public function test_migration_preserves_existing_tokens(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['device_token' => 'existing']);
        $migration = require database_path('migrations/2026_11_16_000001_create_user_devices_table.php');
        $migration->down();
        $migration->up();
        $migration->up();
        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id, 'fcm_token' => 'existing']);
    }

    public function test_web_logout_unregisters_only_its_session_device(): void
    {
        $user = User::factory()->create();
        app(DeviceRegistrationService::class)->register($user, 'phone', 'android', 'phone-token');
        $this->actingAs($user)->postJson('/device/register', ['device_id' => 'browser', 'platform' => 'web', 'fcm_token' => 'web-token'])->assertOk();
        $this->get('/logout')->assertRedirect();
        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', ['device_id' => 'phone']);
    }

    public function test_api_logout_removes_only_the_requested_device(): void
    {
        $user = User::factory()->create();
        $devices = app(DeviceRegistrationService::class);
        $devices->register($user, 'phone', 'android', 'phone-token');
        $devices->register($user, 'browser', 'web', 'web-token');
        $token = auth('api')->login($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout', ['device_id' => 'phone'])->assertOk();
        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', ['device_id' => 'browser']);
    }
}
