<?php 

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

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
        if (!$user) return;

        $title = 'New Lead Assigned';
        $message = 'A new lead has been assigned to you.';

        $payload = [
            'type' => 'LEAD_ASSIGNED',
            'leadId' => (string)$lead->id,
            'leadName' => (string)$lead->name,
            'sourceWebsite' => (string)$lead->source_website,
        ];

        // 1️⃣ Save in database
        Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => 'LEAD_ASSIGNED',
            'data'    => $payload,
        ]);

        // 2️⃣ Send push notification
        if (!empty($user->device_token)) {
            $this->firebase->sendToDevice(
                $user->device_token,
                $title,
                $message,
                $payload
            );
        }
    }
	
	public function sendLeadReminderNotification($userId, $lead)
	{
		$user = User::find($userId);
		if (!$user) return;

		$title = 'Lead Reminder';
		$message = "Lead '{$lead->name}' has no updates from last 2 days. Please follow up.";

		$payload = [
			'type' => 'LEAD_REMINDER',
			'leadId' => (string)$lead->id,
			'leadName' => (string)$lead->name,
		];

		//Save in DB
		Notification::create([
			'user_id' => $userId,
			'title'   => $title,
			'message' => $message,
			'type'    => 'LEAD_REMINDER',
			'data'    => $payload,
		]);

		// Push notification
		if (!empty($user->device_token)) {
			$this->firebase->sendToDevice(
				$user->device_token,
				$title,
				$message,
				$payload
			);
		} 
	}
	
	public function sendLeadStatusUpdateNotification($lead, $oldStatus)
	{
		//admin
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
			'leadId' => (string)$lead->id,
			'leadName' => (string)$lead->name,
			'oldStatus' => (string)$oldStatus,
			'newStatus' => (string)$lead->status,
		];

		foreach ($users as $user) {

			//Save in DB
			Notification::create([
				'user_id' => $user->id,
				'title'   => $title,
				'message' => $message,
				'type'    => 'LEAD_STATUS_UPDATE',
				'data'    => $payload,
			]);

			//Push Notification (Firebase)
			if (!empty($user->device_token)) {
				$this->firebase->sendToDevice(
					$user->device_token,
					$title,
					$message,
					$payload
				);
			}
		}
	}
	
	
    public function sendToRole($role, $data)
	{
		$users = User::role($role)->get();

		foreach ($users as $user) {

			// DB
			Notification::create([
				'user_id' => $user->id,
				'title'   => $data['title'],
				'message' => $data['message'],
				'type'    => $data['type'],
				'data'    => $data['data'] ?? null,
			]);

			// Push
			if (!empty($user->device_token)) {
				$this->firebase->sendToDevice(
					$user->device_token,
					$data['title'],
					$data['message'],
					$data['data'] ?? []
				);
			}
		}
	}
}