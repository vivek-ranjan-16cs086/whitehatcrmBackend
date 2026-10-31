<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_list_returns_unread_count_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Lead assigned',
            'message' => 'A lead was assigned to you.',
            'type' => 'lead_assigned',
            'data' => ['leadId' => '123'],
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Reminder',
            'message' => 'Follow up on a lead.',
            'type' => 'lead_reminder',
            'is_read' => true,
        ]);

        Notification::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Another user notification',
            'message' => 'This should not appear.',
            'type' => 'lead_assigned',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->getJson(route('admin.notifications.list'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.lead_id', '123');
    }

    public function test_lead_notification_links_to_the_leads_detail_action(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Lead assigned',
            'message' => 'A lead was assigned to you.',
            'type' => 'LEAD_ASSIGNED',
            'data' => ['leadId' => '123'],
        ]);

        $this->actingAs($user)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee(
                'data-lead-url="' . route('lead-management.index', ['open_lead' => '123']) . '"',
                false
            );
    }

    public function test_mark_all_as_read_only_updates_the_authenticated_users_unread_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $unreadNotification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Lead assigned',
            'message' => 'A lead was assigned to you.',
            'type' => 'lead_assigned',
            'is_read' => false,
        ]);
        $readNotification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Reminder',
            'message' => 'Follow up on a lead.',
            'type' => 'lead_reminder',
            'is_read' => true,
        ]);
        $otherUserNotification = Notification::create([
            'user_id' => $otherUser->id,
            'title' => 'Another user notification',
            'message' => 'This should remain unread.',
            'type' => 'lead_assigned',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('admin.notifications.readAll'))
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('unread_count', 0);

        $this->assertDatabaseHas('notifications', [
            'id' => $unreadNotification->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $readNotification->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherUserNotification->id,
            'is_read' => false,
        ]);
    }

    public function test_notifications_page_offers_lead_and_mark_all_actions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::create([
            'name' => 'view.lead',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo($permission);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Lead assigned',
            'message' => 'A lead was assigned to you.',
            'type' => 'lead_assigned',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('View all leads')
            ->assertSee('Mark all as read')
            ->assertSee(route('lead-management.index'));
    }
}
