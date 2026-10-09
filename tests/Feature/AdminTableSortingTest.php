<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTableSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_are_sorted_by_the_requested_column(): void
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

        $this->actingAs($user);
        Lead::create(['name' => 'Zulu']);
        Lead::create(['name' => 'Alpha']);

        $response = $this->get(route('lead-management.ajax-list', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 1, 'dir' => 'asc']],
            'columns' => [['data' => null], ['data' => 'name']],
        ]));

        $response->assertOk()
            ->assertJsonPath('aaData.0.name', 'Alpha')
            ->assertJsonPath('aaData.1.name', 'Zulu');
    }

    public function test_targets_are_sorted_by_the_requested_column(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Project',
            'email' => 'project@example.com',
            'phone' => '1234567890',
        ]);

        Target::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'booking_set' => 9,
            'start_day' => '2026-01-01',
            'end_day' => '2026-01-31',
        ]);
        Target::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'booking_set' => 2,
            'start_day' => '2026-01-01',
            'end_day' => '2026-01-31',
        ]);

        $response = $this->actingAs($user)->get(route('admin.targets.ajaxList', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 3, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id'],
                ['data' => 'user'],
                ['data' => 'project'],
                ['data' => 'booking_set'],
            ],
        ]));

        $response->assertOk()
            ->assertJsonPath('aaData.0.booking_set', 2)
            ->assertJsonPath('aaData.1.booking_set', 9);
    }

    public function test_users_are_sorted_by_the_requested_column(): void
    {
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['name' => 'Zulu Admin']);
        $admin->assignRole($role);
        User::factory()->create(['name' => 'Alpha User']);
        User::factory()->create(['name' => 'Beta User']);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.users.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 1, 'dir' => 'asc']],
            'columns' => [['data' => 'serialNo'], ['data' => 'name']],
            ]));

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha User')
            ->assertJsonPath('data.1.name', 'Beta User');
    }
}
