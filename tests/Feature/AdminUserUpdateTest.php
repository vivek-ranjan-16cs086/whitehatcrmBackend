<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Role::create(['name' => 'sales', 'guard_name' => 'web']);
        $this->actingAs($admin)->withHeader('X-Requested-With', 'XMLHttpRequest');
    }

    public function test_admin_can_change_password_while_keeping_the_users_existing_email(): void
    {
        $user = User::factory()->create();
        $this->postJson(route('admin.users.update', $user), [
            '_method' => 'PUT', 'name' => 'Updated Name', 'email' => $user->email,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!', 'role' => 'sales',
        ])->assertOk()->assertJsonPath('status', true);

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertTrue($user->hasRole('sales'));
    }

    public function test_admin_can_update_profile_with_unchanged_email_and_blank_password(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $this->putJson(route('admin.users.update', $user), [
            'name' => 'Profile Updated', 'email' => $user->email,
            'password' => '', 'password_confirmation' => '', 'role' => 'sales',
        ])->assertOk();

        $this->assertSame('Profile Updated', $user->fresh()->name);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_another_users_email_is_still_rejected_without_changing_profile_or_password(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $originalPassword = $user->password;
        $this->putJson(route('admin.users.update', $user), [
            'name' => 'Should Not Save', 'email' => $other->email,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!', 'role' => 'sales',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_username_is_saved_on_creation_and_returned_in_the_edit_payload(): void
    {
        $this->postJson(route('admin.users.store'), ['name' => 'New User', 'username' => 'new.user',
            'email' => 'new@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'role' => 'sales'])
            ->assertOk()->assertJsonPath('user.username', 'new.user');
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->getJson(route('admin.users.index', ['start' => 0, 'length' => 20]))->assertOk()
            ->assertJsonPath('data.0.username', 'new.user');
        $html = view('admin.users.partials.actions', compact('user'))->render();
        preg_match('/data-user="([^"]+)"/', $html, $matches);
        $this->assertSame('new.user', json_decode(html_entity_decode($matches[1], ENT_QUOTES), true)['username']);
    }

    public function test_existing_username_can_be_kept_or_changed_and_duplicates_are_rejected(): void
    {
        $user = User::factory()->create(['username' => 'existing.user']);
        $other = User::factory()->create(['username' => 'other.user']);
        $payload = ['name' => $user->name, 'email' => $user->email, 'role' => 'sales'];
        $this->putJson(route('admin.users.update', $user), $payload + ['username' => 'existing.user'])->assertOk();
        $this->putJson(route('admin.users.update', $user), $payload + ['username' => ' UPDATED.USER '])->assertOk();
        $this->assertSame('updated.user', $user->fresh()->username);
        $this->putJson(route('admin.users.update', $user), $payload + ['username' => $other->username])->assertUnprocessable()->assertJsonValidationErrors('username');
        $this->assertSame('updated.user', $user->fresh()->username);
    }
}
