<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_redirect_guests_to_login(): void
    {
        foreach ([
            '/dashboard',
            '/lead-managment/',
            '/projects/',
            '/targets/',
            '/notifications/',
            '/admin/users',
            '/admin/roles',
            '/admin/permissions',
            '/admin/settings',
        ] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_guest_cannot_update_admin_settings(): void
    {
        $this->post('/admin/settings', [
            'maintenance_mode' => true,
            'maintenance_message' => 'Maintenance',
        ])->assertRedirect(route('login'));
    }

    public function test_super_admin_can_open_settings_page(): void
    {
        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('action="' . route('admin.maintenance.update') . '"', false);
    }

    public function test_super_admin_can_open_target_page_without_salesperson_role(): void
    {
        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/targets/')
            ->assertOk();
    }

    public function test_user_form_lists_configured_roles_dynamically(): void
    {
        $superAdminRole = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'property-consultant',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($superAdminRole);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('value="property-consultant"', false);
    }

    public function test_permissions_page_exposes_explicit_team_lead_access(): void
    {
        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/permissions')
            ->assertOk()
            ->assertSee('view_team.lead');

        $this->assertDatabaseHas('permissions', [
            'name' => 'view_team.lead',
            'guard_name' => 'web',
        ]);
    }
}
