<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadResponse;
use App\Models\LeadTimelineEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_edit_records_transfers_and_preserves_owner_when_omitted(): void
    {
        $this->mock(\App\Services\NotificationService::class)->shouldIgnoreMissing();
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $owner = User::factory()->create();
        $lead = Lead::create(['name' => 'Owned lead', 'assigned_to' => $admin->id]);
        $this->actingAs($admin)->postJson(route('lead-managment.update'), [
            'id' => $lead->id, 'name' => 'Owned lead', 'assigned_to' => $owner->id, 'source_website' => 'facebook.com',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('lead_timeline_events', [
            'lead_id' => $lead->id, 'event_type' => 'TRANSFER', 'assigned_to_user_id' => $owner->id,
        ]);
        $this->postJson(route('lead-managment.update'), ['id' => $lead->id, 'name' => 'Renamed lead', 'source_website' => 'facebook.com'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame($owner->id, $lead->fresh()->assigned_to);
        $this->assertSame(1, LeadTimelineEvent::where('lead_id', $lead->id)->count());
    }

    public function test_admin_gets_full_details_responses_and_paginated_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $owner = User::factory()->create();
        $lead = Lead::create(['name' => 'Detailed lead', 'assigned_to' => $owner->id, 'message' => str_repeat('Full message ', 100)]);
        LeadResponse::create(['lead_id' => $lead->id, 'user_id' => $owner->id, 'note' => 'Response note']);
        for ($i = 0; $i < 27; $i++) {
            LeadTimelineEvent::create(['lead_id' => $lead->id, 'user_id' => $owner->id, 'event_type' => $i === 0 ? 'TRANSFER' : 'COMMENT', 'message' => 'Event '.$i, 'assigned_to_user_id' => $owner->id]);
        }
        $this->actingAs($admin)->getJson(route('lead-management.details', $lead))
            ->assertOk()->assertJsonPath('current_owner', $owner->name)
            ->assertJsonFragment(['label' => 'Message', 'value' => str_repeat('Full message ', 100)])
            ->assertJsonPath('sections.responses.items.0.message', 'Response note')
            ->assertJsonPath('sections.timeline.total', 27)->assertJsonCount(25, 'sections.timeline.items')
            ->assertJsonPath('sections.timeline.has_more', true)->assertJsonPath('sections.ownership.total', 1);
        $this->getJson(route('lead-management.details', [$lead, 'section' => 'timeline', 'page' => 2]))
            ->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('has_more', false);
    }

    public function test_assignee_sees_own_private_activity_and_outsider_cannot_open_lead(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::create(['name' => 'view.lead', 'guard_name' => 'web']));
        $other = User::factory()->create();
        $lead = Lead::create(['name' => 'Private lead', 'assigned_to' => $viewer->id]);
        foreach ([$viewer, $other] as $user) {
            LeadTimelineEvent::create(['lead_id' => $lead->id, 'user_id' => $user->id, 'event_type' => 'COMMENT', 'message' => 'Private '.$user->id]);
            LeadResponse::create(['lead_id' => $lead->id, 'user_id' => $user->id, 'note' => 'Note '.$user->id]);
        }
        $this->actingAs($viewer)->getJson(route('lead-management.details', $lead))
            ->assertOk()->assertJsonPath('sections.timeline.total', 1)->assertJsonPath('sections.responses.total', 1)
            ->assertJsonMissing(['message' => 'Private '.$other->id]);
        $lead->update(['assigned_to' => $other->id]);
        $this->getJson(route('lead-management.details', $lead))->assertNotFound();
    }

    public function test_recording_cannot_be_requested_from_a_different_lead(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $lead = Lead::create(['name' => 'One']);
        $other = Lead::create(['name' => 'Two']);
        $response = LeadResponse::create(['lead_id' => $other->id, 'user_id' => $admin->id, 'note' => 'Call', 'call_recording' => 'call_recordings/test.mp3']);
        $this->actingAs($admin)->get(route('lead-management.recording', [$lead, $response]))->assertNotFound();
    }
}
