<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name', 'project_id', 'team_lead_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }
}
