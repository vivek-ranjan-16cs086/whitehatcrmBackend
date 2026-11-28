<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadTimelineEvent;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DueFollowUpReminder extends Command
{
    protected $signature = 'crm:due-followup-reminder';

    protected $description = 'Remind assignees 30 minutes before follow-ups and expire past follow-ups';

    public function handle(NotificationService $notifications): int
    {
        $now = now()->toImmutable();
        $expired = Lead::where('status', 'follow_up')->where('follow_up_date', '<', $now);

        (clone $expired)->select('id')->eachById(function (Lead $candidate) use ($expired) {
            DB::transaction(function () use ($expired, $candidate) {
                $lead = (clone $expired)->whereKey($candidate->id)->lockForUpdate()->first();
                if (!$lead) {
                    return;
                }

                $lead->update(['status' => 'follow_up_expired']);
                LeadTimelineEvent::create([
                    'lead_id' => $lead->id,
                    'user_id' => $lead->assigned_to,
                    'user_name' => 'System',
                    'event_type' => 'FOLLOW_UP_EXPIRED',
                    'follow_up_date' => $lead->follow_up_date,
                    'message' => 'Follow-up expired automatically.',
                ]);
            });
        });

        Lead::dueForFollowUpReminder($now)
            ->select('id')->eachById(fn (Lead $lead) => $notifications->sendDueFollowUpNotification($lead->id, $now));

        return self::SUCCESS;
    }
}
