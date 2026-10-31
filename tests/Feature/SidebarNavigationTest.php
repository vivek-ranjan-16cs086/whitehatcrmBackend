<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sidebar_includes_lead_actions_workspace_and_admin_sections(): void
    {
        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson(route('admin.sidebar.menu'))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Dashboard', 'section' => 'Overview'])
            ->assertJsonFragment(['label' => 'All Leads', 'section' => 'Lead operations'])
            ->assertJsonFragment(['label' => 'Unassigned leads'])
            ->assertJsonFragment(['label' => 'Overdue follow-ups'])
            ->assertJsonFragment(['label' => 'No follow-up set'])
            ->assertJsonFragment(['label' => 'Stale leads · 7+ days'])
            ->assertJsonFragment(['label' => 'Notifications', 'badge' => 'notifications'])
            ->assertJsonFragment(['label' => 'Settings', 'section' => 'Administration']);
    }

    public function test_sidebar_only_shows_lead_navigation_to_users_with_lead_access(): void
    {
        $permission = Permission::create([
            'name' => 'view.lead',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => 'lead-viewer',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        $leadViewer = User::factory()->create();
        $leadViewer->assignRole($role);

        $this->actingAs($leadViewer)
            ->getJson(route('admin.sidebar.menu'))
            ->assertOk()
            ->assertJsonFragment(['label' => 'All Leads', 'section' => 'Lead operations'])
            ->assertJsonMissing(['label' => 'Access Control'])
            ->assertJsonMissing(['label' => 'Settings']);

        $basicUser = User::factory()->create();

        $this->actingAs($basicUser)
            ->getJson(route('admin.sidebar.menu'))
            ->assertOk()
            ->assertJsonMissing(['label' => 'All Leads'])
            ->assertJsonFragment(['label' => 'Notifications']);
    }
}
