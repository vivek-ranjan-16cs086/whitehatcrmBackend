<?php

namespace App\Services;

use App\Events\ChatUpdated;
use App\Models\ChatConversation;

class ChatRealtimeService
{
    public function enabled(): bool
    {
        return config('broadcasting.default') === 'pusher'
            && filled(config('broadcasting.connections.pusher.app_id'))
            && filled(config('broadcasting.connections.pusher.key'))
            && filled(config('broadcasting.connections.pusher.secret'));
    }

    public function notify(ChatConversation $conversation, string $kind, array $data = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        $recipients = $conversation->members()->where('enable', true)->pluck('users.id')->all();
        if (! $recipients) {
            return;
        }
        try {
            // Synchronous delivery works on shared hosting without a queue worker.
            event(new ChatUpdated($recipients, array_merge($data, [
                'kind' => $kind, 'conversation_id' => $conversation->id,
            ])));
        } catch (\Throwable $exception) {
            // The committed message remains available through history and polling.
            report($exception);
        }
    }
}
