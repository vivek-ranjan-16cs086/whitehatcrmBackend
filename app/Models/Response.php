<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    protected $fillable = [
        'name'
    ];

    public function subResponses()
    {
        return $this->hasMany(SubResponse::class);
    }
}