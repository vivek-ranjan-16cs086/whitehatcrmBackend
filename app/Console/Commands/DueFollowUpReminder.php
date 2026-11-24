<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class DueFollowUpReminder extends Command
{
    protected $signature = 'crm:due-followup-reminder';

    protected $description = 'Send salespeople a digest of the next 12 hours of lead follow-ups';

    public function handle(NotificationService $notifications): int
    {
        $now = now('Asia/Kolkata')->toImmutable();
        $morning = $now->setTime(6, 0);
        $evening = $now->setTime(17, 35);
        $from = match (true) {
            $now >= $evening => $evening,
            $now >= $morning => $morning,
            default => $evening->subDay(),
        };
        $until = $from->addHours(12);
        $dueLeads = Lead::dueForFollowUp($from, $until);

        User::query()
            ->where('enable', true)
            ->whereIn('id', (clone $dueLeads)->select('assigned_to'))
            ->eachById(function (User $user) use ($dueLeads, $notifications, $from, $until) {
                $leads = (clone $dueLeads)->where('assigned_to', $user->id)
                    ->orderBy('follow_up_date')
                    ->orderBy('id')
                    ->limit(5)
                    ->get();

                if ($leads->isNotEmpty()) {
                    $notifications->sendDueFollowUpNotification($user, $leads, $from, $until);
                }
            });

        return self::SUCCESS;
    }
}
