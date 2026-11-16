<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadInlineUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }

    public function test_visible_dropdowns_and_visit_date_save_independently_and_record_history(): void
    {
        $admin = $this->admin();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'active', 'type' => 'cold', 'inventory' => '2 BHK', 'visit_status' => 'scheduled']);
        Lead::create(['name' => 'Option source', 'status' => 'booked', 'type' => 'hot', 'inventory' => '3 BHK', 'visit_status' => 'visited']);
        $project = Project::create(['name' => 'Project', 'email' => 'project@example.com', 'phone' => '1234567890']);
        $this->actingAs($admin);
        foreach (['status' => 'booked', 'type' => 'hot', 'inventory' => '3 BHK', 'project_id' => $project->id, 'visit_status' => 'visited', 'visit_date' => '2026-11-20'] as $field => $value) {
            $this->postJson(route('lead-management.update-field'), ['id' => $lead->id, 'field' => $field, 'value' => $value, 'name' => 'Tampered'])
                ->assertOk()->assertJsonPath('success', true);
            $this->assertEquals($value, substr((string) $lead->fresh()->getRawOriginal($field), 0, strlen((string) $value)));
        }
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'name' => 'Buyer', 'status' => 'booked', 'type' => 'hot']);
        $this->assertDatabaseCount('lead_timeline_events', 6);
        $this->postJson(route('lead-management.update-field'), ['id' => $lead->id, 'field' => 'type', 'value' => 'hot'])->assertOk();
        $this->assertDatabaseCount('lead_timeline_events', 6);
        $this->getJson(route('lead-management.ajax-list', ['filters' => ['name' => 'Buyer']]))->assertOk()
            ->assertJsonMissing(['editBtn'])->assertDontSee('editBtn');
    }

    public function test_deactivation_requires_valid_reason_and_reactivation_clears_it(): void
    {
        $admin = $this->admin();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'active']);
        Lead::create(['name' => 'Deactive option', 'status' => 'deactive']);
        $this->actingAs($admin);
        $payload = ['id' => $lead->id, 'field' => 'status', 'value' => 'deactive'];
        $this->postJson(route('lead-management.update-field'), $payload)->assertUnprocessable();
        $this->postJson(route('lead-management.update-field'), $payload + ['deactivate_reason' => 'Invalid'])->assertUnprocessable();
        $this->postJson(route('lead-management.update-field'), $payload + ['deactivate_reason' => 'Budget'])->assertOk();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'deactive', 'deactivate_reason' => 'Budget']);
        $this->postJson(route('lead-management.update-field'), ['id' => $lead->id, 'field' => 'status', 'value' => 'active'])->assertOk();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'active', 'deactivate_reason' => null]);
    }

    public function test_invalid_fields_and_values_cannot_modify_lead(): void
    {
        $admin = $this->admin();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'active']);
        $this->actingAs($admin);
        foreach ([['field' => 'assigned_to', 'value' => $admin->id], ['field' => 'name', 'value' => 'Tampered'], ['field' => 'status', 'value' => 'invented'], ['field' => 'project_id', 'value' => 999999], ['field' => 'visit_date', 'value' => 'invalid']] as $change) {
            $this->postJson(route('lead-management.update-field'), ['id' => $lead->id] + $change)->assertUnprocessable();
        }
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'name' => 'Buyer', 'status' => 'active']);
        $this->assertDatabaseCount('lead_timeline_events', 0);
    }

    public function test_edit_permission_and_lead_scope_are_enforced(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'active']);
        $payload = ['id' => $lead->id, 'field' => 'status', 'value' => null];
        $this->actingAs($user)->postJson(route('lead-management.update-field'), $payload)->assertForbidden();
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'edit.lead', 'guard_name' => 'web']));
        $user->assignRole($role);
        $this->postJson(route('lead-management.update-field'), $payload)->assertNotFound();
        $lead->update(['assigned_to' => $user->id]);
        $this->postJson(route('lead-management.update-field'), $payload)->assertOk();
    }
}
