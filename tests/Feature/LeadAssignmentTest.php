<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }

    public function test_superadmin_can_assign_reassign_and_unassign_without_changing_other_fields(): void
    {
        $admin = $this->admin();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'deactive']);
        $lead->forceFill(['deactivate_reason' => 'Existing reason', 'notes' => 'Keep notes'])->save();
        $this->mock(NotificationService::class, function ($mock) use ($first, $second) {
            $mock->shouldReceive('sendLeadAssignedNotification')->once()->with($first->id, \Mockery::type(Lead::class));
            $mock->shouldReceive('sendLeadAssignedNotification')->once()->with($second->id, \Mockery::type(Lead::class));
        });
        $this->actingAs($admin);
        foreach ([$first->id, $second->id, null] as $owner) {
            $this->postJson(route('lead-management.assign'), [
                'id' => $lead->id, 'assigned_to' => $owner, 'status' => 'active', 'notes' => 'Tampered',
            ])->assertOk()->assertJsonPath('success', true);
            $this->assertEquals($owner, $lead->fresh()->assigned_to);
        }
        $this->assertDatabaseCount('lead_timeline_events', 3);
        $this->assertDatabaseHas('lead_timeline_events', ['lead_id' => $lead->id, 'user_id' => $admin->id, 'event_type' => 'TRANSFER', 'assigned_to_user_id' => $second->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'deactive', 'notes' => 'Keep notes', 'deactivate_reason' => 'Existing reason']);
    }

    public function test_reselecting_the_same_owner_does_not_notify_or_record_a_transfer(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $lead = Lead::create(['name' => 'Buyer', 'assigned_to' => $owner->id]);
        $this->mock(NotificationService::class)->shouldNotReceive('sendLeadAssignedNotification');
        $this->actingAs($admin)->postJson(route('lead-management.assign'), ['id' => $lead->id, 'assigned_to' => $owner->id])->assertOk();
        $this->assertDatabaseCount('lead_timeline_events', 0);
    }

    public function test_non_superadmin_cannot_use_inline_assignment_even_with_edit_permission(): void
    {
        $role = Role::create(['name' => 'sales', 'guard_name' => 'web']);
        $role->givePermissionTo(\Spatie\Permission\Models\Permission::create(['name' => 'edit.lead', 'guard_name' => 'web']));
        $user = User::factory()->create();
        $user->assignRole($role);
        $lead = Lead::create(['name' => 'Buyer', 'assigned_to' => $user->id]);
        $this->actingAs($user)->postJson(route('lead-management.assign'), ['id' => $lead->id, 'assigned_to' => null])->assertForbidden();
        $this->assertEquals($user->id, $lead->fresh()->assigned_to);
    }

    public function test_invalid_or_missing_owner_does_not_clear_assignment(): void
    {
        $admin = $this->admin();
        $lead = Lead::create(['name' => 'Buyer', 'assigned_to' => $admin->id]);
        $this->actingAs($admin);
        $this->postJson(route('lead-management.assign'), ['id' => $lead->id])->assertUnprocessable();
        $this->postJson(route('lead-management.assign'), ['id' => $lead->id, 'assigned_to' => 999999])->assertUnprocessable();
        $this->assertEquals($admin->id, $lead->fresh()->assigned_to);
    }

    public function test_edit_form_can_explicitly_clear_assignment_and_list_returns_null(): void
    {
        $admin = $this->admin();
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'active', 'assigned_to' => $admin->id]);
        $this->actingAs($admin)->postJson(route('lead-managment.update'), ['id' => $lead->id, 'assigned_to' => ''])->assertOk();
        $this->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20]))->assertOk()
            ->assertJsonPath('data.0.edit_data.assigned_to', null)->assertJsonPath('data.0.assigned_to', 'Unassigned');
    }

    public function test_user_choices_include_roles(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create(['name' => 'Assignment Person']);
        $owner->assignRole(Role::create(['name' => 'business-executive', 'guard_name' => 'web']));
        $this->actingAs($admin)->getJson(route('lead-management.assignees', ['q' => 'Assignment Person']))->assertOk()
            ->assertJsonPath('results.0.id', $owner->id)->assertJsonPath('results.0.text', 'Assignment Person ? business-executive');
        $this->get(route('lead-management.index'))->assertOk()->assertViewHas('assignmentUsers', fn ($users) => collect($users)->contains(fn ($user) => $user['id'] === $owner->id && str_contains($user['text'], 'business-executive')));
    }
}
