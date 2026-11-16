<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function sendToUser(\App\Models\User $user, string $title, string $body, array $data = []): void
    {
        foreach ($user->devices()->get() as $device) {
            try {
                $this->sendToDevice($device->fcm_token, $title, $body, $data);
            } catch (\Kreait\Firebase\Exception\MessagingException $exception) {
                $errors = $exception->errors();
                $unregistered = collect($errors['error']['details'] ?? [])->contains(
                    fn ($detail) => ($detail['errorCode'] ?? null) === 'UNREGISTERED'
                );
                if ($unregistered) {
                    $user->devices()->whereKey($device->id)->where('token_hash', $device->token_hash)->delete();
                } else {
                    report($exception);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = [])
    {
        if (! $this->messaging) {
            $this->messaging = (new Factory)->withServiceAccount(
                storage_path('app/firebase/firebase-service-account.json')
            )->createMessaging();
        }

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        return $this->messaging->send($message);
    }
}
