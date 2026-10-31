<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatUpdated implements ShouldBroadcastNow
{
    public function __construct(public array $recipients, public array $payload) {}

    public function broadcastOn(): array
    {
        return array_map(fn ($id) => new PrivateChannel('chat.user.'.$id), $this->recipients);
    }

    public function broadcastAs(): string
    {
        return 'chat.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
