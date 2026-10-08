<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadTimelineEvent extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'user_name',
        'user_role',
        'event_type',
		'message',
        'follow_up_date',
        'call_outcome',
        'phone',
        'assigned_to_user_id',
        'assignment_context_id'
    ];
	
	protected $casts = [
		'follow_up_date' => 'datetime',
	];
}