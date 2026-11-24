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

    public function test_evening_digest_sends_once_per_recipient_and_lists_the_five_earliest_due_leads(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(18, 0));
        $salesperson = User::factory()->create();
        $other = User::factory()->create();
        $earliest = $this->lead($salesperson, 0);
        foreach ([6, 5, 4, 3, 2] as $hours) {
            $this->lead($salesperson, $hours);
        }
        $this->lead($other, 12);
        $this->lead($other, -1);
        $this->lead($other, 2, 'follow_up_complete');

        $this->mock(FirebaseNotificationService::class)
            ->shouldReceive('sendToUser')
            ->once()
            ->withArgs(fn ($user, $title, $message, $data) => $user->is($salesperson)
                && $data['type'] === Notification::DUE_FOLLOWUP_LEADS
                && count(json_decode($data['leads'], true)) === 5);

        $this->artisan('crm:due-followup-reminder')->assertSuccessful();
        $this->artisan('crm:due-followup-reminder')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $notification = Notification::sole();
        $this->assertSame(Notification::DUE_FOLLOWUP_LEADS, $notification->type);
        $this->assertCount(5, $notification->data['leads']);
        $this->assertSame((string) $earliest->id, $notification->data['leads'][0]['id']);
        $this->assertSame(now()->addHours(5)->toIso8601String(), $notification->data['leads'][4]['follow_up_date']);
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
