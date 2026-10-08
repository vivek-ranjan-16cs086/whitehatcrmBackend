<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertJsonCount(2, 'data');
    }
}
