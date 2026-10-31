<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    protected $fillable = ['type', 'name', 'direct_key', 'created_by'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_members', 'conversation_id', 'user_id')
            ->withPivot('last_read_message_id')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }
}
