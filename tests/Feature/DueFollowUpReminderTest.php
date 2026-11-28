<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DueFollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_is_sent_once_per_assignee_and_scheduled_time(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(18, 11));
        $salesperson = User::factory()->create();
        $lead = $this->lead($salesperson, 0, 'follow_up');
        $lead->update(['follow_up_date' => now()->addMinutes(30)]);
        $this->lead($salesperson, 1);
        $this->lead($salesperson, 0);
        $this->lead($salesperson, 0, 'follow_up_complete')->update(['follow_up_date' => now()->addMinutes(20)]);
        $disabled = User::factory()->create(['enable' => false]);
        $this->lead($disabled, 0)->update(['follow_up_date' => now()->addMinutes(20)]);

        $this->mock(FirebaseNotificationService::class)
            ->shouldReceive('sendToUser')
            ->twice()
            ->withArgs(fn ($user, $title, $message, $data) => $user->is($salesperson)
                && $data['type'] === Notification::DUE_FOLLOWUP_LEADS
                && $data['leadId'] === (string) $lead->id
                && count(json_decode($data['leads'], true)) === 1);

        $this->artisan('crm:due-followup-reminder')->assertSuccessful();
        $notification = Notification::sole();
        $this->assertSame(now()->addMinutes(30)->toIso8601String(), $notification->data['follow_up_date']);
        $notification->delete();
        $this->travel(1)->minute();
        $this->artisan('crm:due-followup-reminder')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $lead->update(['follow_up_date' => now()->addMinutes(30)]);
        $this->artisan('crm:due-followup-reminder')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_command_expires_only_past_pending_follow_ups_and_records_the_original_due_time(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(18, 11));
        $user = User::factory()->create();
        $past = $this->lead($user, -1, 'follow_up');
        $now = $this->lead($user, 0, 'follow_up');
        $future = $this->lead($user, 1, 'follow_up');
        $complete = $this->lead($user, -1, 'follow_up_complete');

        $this->artisan('crm:due-followup-reminder')->assertSuccessful();
        $this->artisan('crm:due-followup-reminder')->assertSuccessful();

        $this->assertSame('follow_up_expired', $past->fresh()->status);
        $this->assertSame('follow_up', $now->fresh()->status);
        $this->assertSame('follow_up', $future->fresh()->status);
        $this->assertSame('follow_up_complete', $complete->fresh()->status);
        $this->assertDatabaseCount('lead_timeline_events', 1);
        $this->assertDatabaseHas('lead_timeline_events', [
            'lead_id' => $past->id,
            'event_type' => 'FOLLOW_UP_EXPIRED',
            'follow_up_date' => $past->follow_up_date->toDateTimeString(),
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_api_returns_all_of_the_callers_due_leads_in_a_rolling_window(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(13, 0));
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'view.lead', 'guard_name' => 'web']));
        $first = $this->lead($user, 0);
        foreach ([11, 5, 4, 3, 2] as $hours) {
            $this->lead($user, $hours);
        }
        $this->lead($user, -1);
        $this->lead($user, 12);
        $this->lead($user, 1, 'deactive');
        $this->lead(User::factory()->create(), 1);

        $this->getJson('/api/v1/due_followup_leads')->assertUnauthorized();
        $this->actingAs($user, 'api')
            ->getJson('/api/v1/due_followup_leads')
            ->assertOk()
            ->assertJsonCount(6, 'data.leads')
            ->assertJsonPath('data.leads.0.id', $first->id);
    }

    private function lead(User $user, int $hours, string $status = 'active'): Lead
    {
        return Lead::create([
            'name' => 'Follow-up lead',
            'assigned_to' => $user->id,
            'status' => $status,
            'follow_up_date' => now()->addHours($hours),
        ]);
    }
}
