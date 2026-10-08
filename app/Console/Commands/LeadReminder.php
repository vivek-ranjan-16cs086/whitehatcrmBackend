<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\LeadNote;
use Carbon\Carbon;
use App\Services\NotificationService;

class LeadReminder extends Command
{
    protected $signature = 'crm:lead-reminder';
    protected $description = 'Send reminder if salesman not updating lead notes';

    public function handle(NotificationService $notificationService)
    {
        $twoDaysAgo = Carbon::now()->subDays(2);

        $leads = Lead::whereNotIn('status', ['booked', 'follow_up_complete'])
            ->get();

        // 
        foreach ($leads as $lead) {

            if (!$lead->assigned_to) {
                continue;
            }

            $lastNote = LeadNote::where('lead_id', $lead->id)
                ->latest()
                ->first();

            if (!$lastNote || $lastNote->created_at <= $twoDaysAgo) {

                $alreadySent = LeadNote::where('lead_id', $lead->id)
                    ->where('type', 'reminder')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if ($alreadySent) continue;

                $notificationService->sendLeadReminderNotification(
                    $lead->assigned_to,
                    $lead
                );

                LeadNote::create([
                    'lead_id' => $lead->id,
                    'user_id' => $lead->assigned_to,
                    'note'    => 'Auto Reminder: No activity in last 2 days.',
                    'type'    => 'reminder',
                ]);
            }
        }

        $this->info('Lead reminders sent successfully.');
    }
}