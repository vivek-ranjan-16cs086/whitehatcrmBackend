<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
		
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
			'phone' => $this->phone,
			'alternate_phone' => $this->alternate_phone,
			'date_of_birth' => $this->date_of_birth,
			'address' => $this->address,
			'profile_pic' => $this->profile_pic,
            'role_id' => $this->role_id,       
            'role_name' => $this->role_name,
            'status' => $this->enable, 
			'last_seen_at' =>$this->last_seen_at,
			'is_online' => $this->last_seen_at
            && $this->last_seen_at->gt(now()->subMinutes(2)),

        ];
    }
}
