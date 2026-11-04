<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $this->actingAs($admin);
        return $admin;
    }

    public function test_teams_can_be_created_searched_updated_and_deleted(): void
    {
        $admin = $this->admin();
        $project = Project::create(['name' => 'Team Project', 'email' => 'project@example.com', 'phone' => '1234567890']);
        $payload = ['name' => 'Sales Team', 'project_id' => $project->id, 'team_lead_id' => $admin->id];
        $this->post(route('admin.teams.store'), $payload)->assertRedirect(route('admin.teams.index'));
        $team = Team::firstOrFail();
        $this->get(route('admin.teams.index', ['search' => 'Team Project']))->assertOk()->assertSee('Sales Team')->assertSee($admin->name);
        $this->get(route('admin.teams.index', ['search' => 'no-match']))->assertOk()->assertDontSee('Sales Team');
        $payload['name'] = 'Updated Team';
        $this->put(route('admin.teams.update', $team), $payload)->assertRedirect();
        $this->assertSame('Updated Team', $team->fresh()->name);
        $menus = $this->getJson(route('admin.sidebar.menu'))->json('data.menus');
        $labels = array_column($menus, 'label');
        $this->assertSame('Team Management', $labels[array_search('User Management', $labels) + 1]);
        $this->assertSame('Access Control', end($labels));
        $this->delete(route('admin.teams.destroy', $team))->assertRedirect();
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_team_management_requires_admin_access_and_valid_references(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.teams.index'))->assertForbidden();
        $this->postJson(route('admin.teams.store'), ['name' => 'Unauthorized'])->assertForbidden();
        $this->admin();
        $this->postJson(route('admin.teams.store'), ['name' => 'Invalid', 'project_id' => 999, 'team_lead_id' => 999])
            ->assertUnprocessable()->assertJsonValidationErrors(['project_id', 'team_lead_id']);
    }

    public function test_deactivation_requires_a_valid_reason_and_saves_it_for_reopening(): void
    {
        $this->admin();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'deactive']);
        $lead->forceFill(['deactivate_reason' => 'Budget'])->save();
        $payload = ['id' => $lead->id, 'status' => 'deactive'];
        $this->postJson(route('lead-managment.update'), $payload)->assertUnprocessable()->assertJsonValidationErrors('deactivate_reason');
        $this->postJson(route('lead-managment.update'), $payload + ['deactivate_reason' => 'Invalid reason'])
            ->assertUnprocessable()->assertJsonValidationErrors('deactivate_reason');
        $this->postJson(route('lead-managment.update'), $payload + ['deactivate_reason' => 'Budget'])->assertOk();
        $this->assertSame('Budget', $lead->fresh()->deactivate_reason);
        $this->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20]))
            ->assertOk()->assertJsonPath('data.0.edit_data.deactivate_reason', 'Budget');
        $this->postJson(route('lead-managment.update'), ['id' => $lead->id, 'status' => 'active'])->assertOk();
        $this->assertNull($lead->fresh()->deactivate_reason);
    }
}
