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
}
