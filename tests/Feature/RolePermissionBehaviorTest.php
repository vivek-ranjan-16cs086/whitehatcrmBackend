<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_renamed_view_only_role_uses_assigned_leads_for_dashboard_and_ranking(): void
    {
        $user = $this->userWithPermissions('business-associate', ['view.lead']);
        $ownLead = Lead::create([
            'name' => 'Assigned lead',
            'status' => 'active',
            'assigned_to' => $user->id,
        ]);
        Lead::create([
            'name' => 'Other lead',
            'status' => 'active',
            'assigned_to' => User::factory()->create()->id,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('data.total_leads', 1);

        $this->getJson('/api/v1/counts')
            ->assertOk()
            ->assertJsonPath('data.total_leads', 1);

        $this->getJson('/api/v1/performance-ranking')
            ->assertOk()
            ->assertJsonPath('scope', 'self')
            ->assertJsonPath('data.user_id', $user->id);

        $this->postJson('/api/v1/sidebar')
            ->assertOk()
            ->assertJsonFragment(['Leads Management']);

        $this->assertDatabaseMissing('roles', ['name' => 'salesperson']);
        $this->assertDatabaseHas('leads', ['id' => $ownLead->id]);
    }

    public function test_custom_role_with_edit_permission_gets_team_performance(): void
    {
        $viewer = $this->userWithPermissions('regional-manager', ['view.lead', 'edit.lead']);
        $assignedUser = User::factory()->create();
        Lead::create([
            'name' => 'Team lead',
            'status' => 'active',
            'assigned_to' => $assignedUser->id,
        ]);

        $this->actingAs($viewer, 'api')
            ->getJson('/api/v1/performance-ranking')
            ->assertOk()
            ->assertJsonPath('scope', 'team')
            ->assertJsonFragment(['user_id' => $assignedUser->id]);
    }

    public function test_permission_sync_does_not_recreate_default_business_roles(): void
    {
        $this->artisan('permission:sync', ['model' => 'Lead'])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('roles', ['name' => 'admin']);
        $this->assertDatabaseMissing('roles', ['name' => 'manager']);
        $this->assertDatabaseMissing('roles', ['name' => 'salesperson']);
    }

    private function userWithPermissions(string $roleName, array $permissionNames): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
