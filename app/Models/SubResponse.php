<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubResponse extends Model
{
    protected $fillable = [
        'response_id',
        'name'
    ];

    public function response()
    {
        return $this->belongsTo(Response::class);
    }
}