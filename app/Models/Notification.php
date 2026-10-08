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
        'is_read'
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
