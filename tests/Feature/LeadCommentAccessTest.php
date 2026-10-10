<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadTimelineEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LeadCommentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_see_their_own_comments_on_an_assigned_lead(): void
    {
        $assignedUser = $this->userWithPermissions('lead-associate', ['view.lead']);
        $otherUser = User::factory()->create();
        $lead = Lead::create([
            'name' => 'Assigned lead',
            'status' => 'active',
            'assigned_to' => $assignedUser->id,
        ]);

        $ownComment = $this->comment($lead, $assignedUser, 'My private comment');
        $this->comment($lead, $otherUser, 'Another user comment');

        $this->actingAs($assignedUser, 'api')
            ->getJson('/api/v1/leads/' . $lead->id . '/comments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownComment->id)
            ->assertJsonPath('data.0.title', 'My private comment');

        $this->getJson('/api/v1/timeline/' . $lead->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'My private comment'])
            ->assertJsonMissing(['title' => 'Another user comment']);
    }

    public function test_reassignment_preserves_comments_and_each_former_assignee_can_see_their_comments_again(): void
    {
        $firstUser = $this->userWithPermissions('first-associate', ['view.lead']);
        $secondUser = $this->userWithPermissions('second-associate', ['view.lead']);
        $admin = $this->superAdmin();
        $lead = Lead::create([
            'name' => 'Reassigned lead',
            'status' => 'active',
            'assigned_to' => $firstUser->id,
        ]);

        $firstComment = $this->comment($lead, $firstUser, 'First user history');
        $secondComment = $this->comment($lead, $secondUser, 'Second user history');

        $assignmentResponse = $this->actingAs($admin, 'api')
            ->postJson('/api/v1/assign-lead', [
                'lead_id' => $lead->id,
                'assigned_to' => $secondUser->id,
            ]);
        $this->assertSame(200, $assignmentResponse->status(), $assignmentResponse->getContent());
        $assignmentResponse->assertJsonPath('status', true);

        $newTimelineEvent = LeadTimelineEvent::create([
            'lead_id' => $lead->id,
            'user_id' => $secondUser->id,
            'user_name' => $secondUser->name,
            'event_type' => 'STATUS',
            'message' => 'Updated after assignment',
        ]);
        LeadTimelineEvent::create([
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'event_type' => 'STATUS',
            'message' => 'Admin update after assignment',
        ]);

        $this->assertDatabaseCount('lead_timeline_events', 5);
        $this->actingAs($secondUser, 'api')
            ->getJson('/api/v1/leads/' . $lead->id . '/comments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $secondComment->id)
            ->assertJsonMissing(['title' => 'First user history']);
        $this->actingAs($firstUser, 'api')
            ->getJson('/api/v1/leads/' . $lead->id . '/comments')
            ->assertNotFound();
        $this->actingAs($secondUser, 'api')
            ->getJson('/api/v1/timeline/' . $lead->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Lead assigned to ' . $secondUser->name])
            ->assertJsonFragment(['title' => 'Updated after assignment'])
            ->assertJsonMissing(['title' => 'Admin update after assignment'])
            ->assertJsonMissing(['title' => 'First user history'])
            ->assertJsonMissing(['title' => 'Second user history']);
        $this->actingAs($admin, 'api')
            ->getJson('/api/v1/timeline/' . $lead->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'First user history'])
            ->assertJsonFragment(['title' => 'Second user history'])
            ->assertJsonFragment(['title' => 'Updated after assignment'])
            ->assertJsonFragment(['title' => 'Admin update after assignment']);

        $this->actingAs($admin, 'api')
            ->postJson('/api/v1/assign-lead', [
                'lead_id' => $lead->id,
                'assigned_to' => $firstUser->id,
            ])
            ->assertOk();

        $this->actingAs($firstUser, 'api')
            ->getJson('/api/v1/leads/' . $lead->id . '/comments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $firstComment->id)
            ->assertJsonMissing(['title' => 'Second user history']);
        $this->getJson('/api/v1/timeline/' . $lead->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Lead assigned to ' . $firstUser->name])
            ->assertJsonMissing(['title' => 'First user history'])
            ->assertJsonMissing(['title' => 'Second user history'])
            ->assertJsonMissing(['title' => 'Updated after assignment'])
            ->assertJsonMissing(['title' => 'Admin update after assignment']);

        $this->assertDatabaseHas('lead_timeline_events', ['id' => $firstComment->id]);
        $this->assertDatabaseHas('lead_timeline_events', ['id' => $secondComment->id]);
        $this->assertDatabaseHas('lead_timeline_events', ['id' => $newTimelineEvent->id]);
    }

    public function test_super_admin_can_see_all_comments_regardless_of_assignment(): void
    {
        $assignedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = $this->superAdmin();
        $lead = Lead::create([
            'name' => 'Admin-visible lead',
            'status' => 'active',
            'assigned_to' => $assignedUser->id,
        ]);

        $this->comment($lead, $assignedUser, 'Assigned user comment');
        $this->comment($lead, $otherUser, 'Other user comment');

        $this->actingAs($admin, 'api')
            ->getJson('/api/v1/leads/' . $lead->id . '/comments')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Assigned user comment'])
            ->assertJsonFragment(['title' => 'Other user comment']);
    }

    public function test_comment_author_comes_from_authentication_not_request_data(): void
    {
        $user = $this->userWithPermissions('comment-author', ['view.lead', 'edit.lead']);
        $forgedAuthor = User::factory()->create();
        $lead = Lead::create([
            'name' => 'Comment author lead',
            'status' => 'active',
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/leads/' . $lead->id . '/comments', [
                'message' => 'Authenticated author comment',
                'user_id' => $forgedAuthor->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('lead_timeline_events', [
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'event_type' => 'COMMENT',
            'message' => 'Authenticated author comment',
        ]);
        $this->assertDatabaseMissing('lead_timeline_events', [
            'lead_id' => $lead->id,
            'user_id' => $forgedAuthor->id,
            'event_type' => 'COMMENT',
            'message' => 'Authenticated author comment',
        ]);
    }

    private function comment(Lead $lead, User $user, string $message): LeadTimelineEvent
    {
        return LeadTimelineEvent::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'event_type' => 'COMMENT',
            'message' => $message,
        ]);
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

    private function superAdmin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
