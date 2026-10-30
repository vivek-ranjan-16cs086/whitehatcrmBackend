<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiLeadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_leads_from_either_api_route(): void
    {
        $this->getJson('/api/v1/leads')->assertUnauthorized();
        $this->getJson('/api/v1/get-leads')->assertUnauthorized();
    }

    public function test_authenticated_user_without_view_permission_cannot_list_leads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_list_leads(): void
    {
        $user = $this->userWithLeadViewPermission();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_super_admin_can_list_leads_without_an_explicit_permission(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_facebook_webhook_retry_updates_the_existing_lead_and_form_page_name(): void
    {
        config(['services.erp.key' => 'test-api-key']);
        $notifications = $this->mock(NotificationService::class);
        $notifications->shouldReceive('sendToRole')->once();

        $payload = [
            'external_id' => '1893247868694559',
            'source_website' => 'facebook.com',
            'name' => 'Rakesh Mathur',
            'phone' => '+919711389347',
            'email' => 'rakesh@example.com',
            'source_page_name' => 'Forest Walk Diwali 2026',
            'message' => 'Facebook Lead Ads | Form: Forest Walk Diwali 2026 | Purchase timeline: Immediate',
        ];

        $this->postJson('/api/v1/leads/store', $payload, ['x-api-key' => 'test-api-key'])
            ->assertOk()
            ->assertJsonPath('data.source_page_name', 'Forest Walk Diwali 2026');

        $payload['source_page_name'] = 'Old form name';
        $payload['message'] = 'Facebook Lead Ads | Form: Forest Walk Diwali 2026 Updated | Purchase timeline: Immediate';

        $this->postJson('/api/v1/leads/store', $payload, ['x-api-key' => 'test-api-key'])
            ->assertOk()
            ->assertJsonPath('data.source_page_name', 'Forest Walk Diwali 2026 Updated')
            ->assertJsonPath('data.message', $payload['message']);

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'external_id' => '1893247868694559',
            'source_website' => 'facebook.com',
            'source_page_name' => 'Forest Walk Diwali 2026 Updated',
            'message' => $payload['message'],
        ]);
    }

    public function test_duplicate_phone_with_a_different_facebook_lead_id_is_still_rejected(): void
    {
        config(['services.erp.key' => 'test-api-key']);
        $notifications = $this->mock(NotificationService::class);
        $notifications->shouldReceive('sendToRole')->twice();

        $this->postJson('/api/v1/leads/store', [
            'external_id' => '1001',
            'source_website' => 'facebook.com',
            'name' => 'Existing Facebook lead',
            'phone' => '1234567890',
        ], ['x-api-key' => 'test-api-key'])->assertOk();

        $this->postJson('/api/v1/leads/store', [
            'external_id' => '1002',
            'source_website' => 'facebook.com',
            'name' => 'Different Facebook lead',
            'phone' => '1234567890',
        ], ['x-api-key' => 'test-api-key'])->assertStatus(409);

        $this->assertDatabaseCount('leads', 1);
    }

    public function test_renamed_view_only_role_cannot_fetch_another_users_lead_by_id(): void
    {
        $user = $this->userWithLeadViewPermission('business-associate');
        $lead = Lead::create([
            'name' => 'Private lead',
            'assigned_to' => User::factory()->create()->id,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads/'.$lead->id)
            ->assertNotFound();
    }

    private function userWithLeadViewPermission(string $roleName = 'lead-reader'): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'view.lead',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
