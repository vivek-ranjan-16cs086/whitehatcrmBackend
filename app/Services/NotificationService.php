<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Notification;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    protected $firebase;

    public function __construct(FirebaseNotificationService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function sendLeadAssignedNotification($userId, $lead)
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $title = 'New Lead Assigned';
        $message = 'A new lead has been assigned to you.';

        $payload = [
            'type' => 'LEAD_ASSIGNED',
            'leadId' => (string) $lead->id,
            'leadName' => (string) $lead->name,
            'sourceWebsite' => (string) $lead->source_website,
        ];

        // 1️⃣ Save in database
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => 'LEAD_ASSIGNED',
            'data' => $payload,
        ]);

        // 2️⃣ Send push notification
        $this->firebase->sendToUser($user,
            $title,
            $message,
            $payload
        );
    }

    public function sendLeadReminderNotification($userId, $lead)
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $title = 'Lead Reminder';
        $message = "Lead '{$lead->name}' has no updates from last 2 days. Please follow up.";

        $payload = [
            'type' => 'LEAD_REMINDER',
            'leadId' => (string) $lead->id,
            'leadName' => (string) $lead->name,
        ];

        // Save in DB
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => 'LEAD_REMINDER',
            'data' => $payload,
        ]);

        // Push notification
        $this->firebase->sendToUser($user,
            $title,
            $message,
            $payload
        );
    }

    public function sendDueFollowUpNotification(
        int $leadId,
        CarbonInterface $now
    ): void {
        $delivery = DB::transaction(function () use ($leadId, $now) {
            $lead = Lead::dueForFollowUpReminder($now)
                ->whereKey($leadId)->lockForUpdate()->first();
            $recipient = $lead ? User::whereKey($lead->assigned_to)->where('enable', true)->first() : null;
            if (!$recipient) {
                return null;
            }

            $scheduledAt = $lead->follow_up_date->toIso8601String();
            if (Notification::withTrashed()
                ->where('user_id', $recipient->id)
                ->where('type', Notification::DUE_FOLLOWUP_LEADS)
                ->where('data->leadId', (string) $lead->id)
                ->where('data->follow_up_date', $scheduledAt)
                ->exists()) {
                return null;
            }

            $data = [
                'type' => Notification::DUE_FOLLOWUP_LEADS,
                'leadId' => (string) $lead->id,
                'follow_up_date' => $scheduledAt,
                'leads' => [[
                    'id' => (string) $lead->id,
                    'name' => $lead->name,
                    'follow_up_date' => $scheduledAt,
                ]],
            ];

            $notification = Notification::create([
                'user_id' => $recipient->id,
                'title' => 'Upcoming Lead Follow-up',
                'message' => sprintf('Follow up with %s at %s.', Str::limit($lead->name, 50), $lead->follow_up_date->format('g:i A')),
                'type' => Notification::DUE_FOLLOWUP_LEADS,
                'data' => $data,
            ]);

            return [$recipient, $notification];
        });

        if ($delivery) {
            [$recipient, $notification] = $delivery;
            $payload = $notification->data;
            $payload['leads'] = json_encode($payload['leads'], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
            $this->firebase->sendToUser($recipient, $notification->title, $notification->message, $payload);
        }
    }

    public function sendLeadStatusUpdateNotification($lead, $oldStatus)
    {
        // admin
        $users = User::role('super-admin')->get();

        $title = 'Lead Status Updated';

        $message = sprintf(
            '%s updated the status of lead "%s" from "%s" to "%s"',
            auth()->user()->name,
            $lead->name,
            ucfirst($oldStatus),
            ucfirst($lead->status)
        );

        $payload = [
            'type' => 'LEAD_STATUS_UPDATE',
            'leadId' => (string) $lead->id,
            'leadName' => (string) $lead->name,
            'oldStatus' => (string) $oldStatus,
            'newStatus' => (string) $lead->status,
        ];

        foreach ($users as $user) {

            // Save in DB
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => 'LEAD_STATUS_UPDATE',
                'data' => $payload,
            ]);

            // Push Notification (Firebase)
            $this->firebase->sendToUser($user,
                $title,
                $message,
                $payload
            );
        }
    }

    public function sendToRole($role, $data)
    {
        $users = User::role($role)->get();

        foreach ($users as $user) {

            // DB
            Notification::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'],
                'data' => $data['data'] ?? null,
            ]);

            // Push
            $this->firebase->sendToUser($user,
                $data['title'],
                $data['message'],
                $data['data'] ?? []
            );
        }
    }
}
