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
}
