<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'data',
        'is_read',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function destinationUrl(): ?string
    {
        $conversationId = data_get($this->data, 'conversationId');
        if ($this->type === 'CHAT_MESSAGE' && $conversationId) {
            return route('admin.chat.index', ['conversation' => $conversationId]);
        }

        $leadId = data_get($this->data, 'leadId') ?? data_get($this->data, 'lead_id');

        return $leadId ? route('lead-management.index', ['open_lead' => $leadId]) : null;
    }
}
