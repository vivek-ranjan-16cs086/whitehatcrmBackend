<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActionRequiredDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_action_counts_and_links_to_matching_leads(): void
    {
        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Lead::create([
            'name' => 'Needs assignment',
            'status' => 'active',
            'assigned_to' => null,
        ]);
        Lead::create([
            'name' => 'Overdue follow-up',
            'status' => 'follow_up',
            'assigned_to' => $admin->id,
            'follow_up_date' => now()->subDay()->toDateString(),
        ]);
        Lead::create([
            'name' => 'Due today',
            'status' => 'follow_up',
            'assigned_to' => $admin->id,
            'follow_up_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Action required')
            ->assertSee('Unassigned leads')
            ->assertSee('Overdue follow-ups')
            ->assertSee('No follow-up set')
            ->assertSee('Stale leads')
            ->assertSee(route('lead-management.index', ['attention' => 'unassigned']), false)
            ->assertSee(route('lead-management.index', ['attention' => 'overdue']), false)
            ->assertSee(route('lead-management.index', ['attention' => 'no-follow-up']), false)
            ->assertSee(route('lead-management.index', ['attention' => 'stale']), false);
    }

    public function test_action_filters_return_only_matching_visible_leads(): void
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
        $user = User::factory()->create();
        $user->assignRole($role);

        $overdue = Lead::create([
            'name' => 'My overdue lead',
            'status' => 'follow_up',
            'assigned_to' => $user->id,
            'follow_up_date' => now()->subDay()->toDateString(),
        ]);
        Lead::create([
            'name' => 'Another users overdue lead',
            'status' => 'follow_up',
            'assigned_to' => User::factory()->create()->id,
            'follow_up_date' => now()->subDay()->toDateString(),
        ]);
        Lead::create([
            'name' => 'Unassigned lead',
            'status' => 'active',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('lead-management.ajax-list', [
            'view' => 'total',
            'attention' => 'overdue',
            'draw' => 1,
            'start' => 0,
            'length' => 20,
        ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.lead_id', $overdue->id);
    }

    public function test_lead_action_filter_rejects_unknown_filter_values(): void
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
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson(route('lead-management.ajax-list', [
                'attention' => 'everyone',
                'draw' => 1,
                'start' => 0,
                'length' => 20,
            ]))
            ->assertUnprocessable();
    }
}
