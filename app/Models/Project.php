<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projects'; 

    protected $fillable = [
        'name',
        'email',
        'phone',
        'enable',
        'address',
		'inventory',
		'min_price',
		'max_price'
    ];

    protected $casts = [
        'enable' => 'boolean',
    ];
}