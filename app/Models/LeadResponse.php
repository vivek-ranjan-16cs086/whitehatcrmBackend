<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadResponse extends Model
{
	use HasFactory;
	
	 protected $fillable = [
        'lead_id',
        'user_id',
		'call_recording',
		'note'
    ];
    public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function lead()
	{
		return $this->belongsTo(Lead::class);
	}
}