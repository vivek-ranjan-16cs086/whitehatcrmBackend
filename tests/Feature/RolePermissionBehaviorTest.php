<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_associate_with_edit_permission_and_no_assignments_sees_no_other_users_data(): void
    {
        $user = $this->userWithPermissions(
            'business-associate',
            ['view.lead', 'edit.lead', 'create.lead']
        );
        $otherUser = User::factory()->create();
        $otherLead = Lead::create([
            'name' => 'Other lead',
            'status' => 'active',
            'assigned_to' => $otherUser->id,
        ]);
        $project = Project::create([
            'name' => 'Target project',
            'email' => 'target@example.test',
            'phone' => '123456789',
        ]);
        $otherTarget = Target::create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'start_day' => '2026-10-05',
            'end_day' => '2026-10-11',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('data.total_leads', 0);

        $this->getJson('/api/v1/leads/' . $otherLead->id)->assertNotFound();
        $this->postJson('/api/v1/leads/update-lead-status/' . $otherLead->id, [
            'status' => 'deactive',
            'note' => 'Attempted update',
        ])->assertNotFound();

        $this->getJson('/api/v1/counts')
            ->assertOk()
            ->assertJsonPath('data.total_leads', 0);

        $this->getJson('/api/v1/performance-ranking')
            ->assertOk()
            ->assertJsonPath('scope', 'self')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.total_leads', 0);

        $this->getJson('/api/v1/pipeline-health')
            ->assertOk()
            ->assertJsonPath('data.pipeline_health', 0);

        $this->getJson('/api/v1/followup-leads?user_id=' . $otherUser->id)
            ->assertOk()
            ->assertJsonPath('data.total', 0);

        $this->getJson('/api/v1/targets?user_id=' . $otherUser->id)
            ->assertOk()
            ->assertJsonPath('data', []);
        $this->getJson('/api/v1/targets/' . $otherTarget->id)->assertNotFound();

        $this->getJson('/api/v1/timeline/' . $otherLead->id)->assertNotFound();
        $this->getJson('/api/v1/leads/' . $otherLead->id . '/comments')->assertNotFound();
        $this->getJson('/api/v1/leads/' . $otherLead->id . '/follow-ups')->assertNotFound();
        $this->getJson('/api/v1/leads/' . $otherLead->id . '/calls')->assertNotFound();
        $this->postJson('/api/v1/leads/' . $otherLead->id . '/comments', [
            'message' => 'Attempted comment',
        ])->assertNotFound();
        $this->postJson('/api/v1/leads/' . $otherLead->id . '/calls', [
            'call_outcome' => 'answered',
        ])->assertNotFound();

        $this->assertDatabaseMissing('roles', ['name' => 'salesperson']);
    }

    public function test_custom_role_needs_explicit_team_permission_to_see_other_users_leads(): void
    {
        $viewer = $this->userWithPermissions(
            'regional-manager',
            ['view.lead', 'view_team.lead']
        );
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

    public function test_permission_sync_creates_explicit_team_visibility_permission(): void
    {
        $this->artisan('permission:sync', ['model' => 'Lead'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('permissions', [
            'name' => 'view_team.lead',
            'guard_name' => 'web',
        ]);
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
