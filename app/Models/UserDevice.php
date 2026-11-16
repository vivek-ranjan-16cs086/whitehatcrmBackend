<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = ['user_id', 'device_id', 'platform', 'fcm_token', 'token_hash', 'last_seen_at'];

    protected $hidden = ['fcm_token', 'token_hash'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
