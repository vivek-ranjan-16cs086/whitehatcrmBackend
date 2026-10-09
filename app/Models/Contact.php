<?php

namespace App\Models;

use App\Traits\HasModelPermissions;
use App\Traits\ScopedToOwner;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasModelPermissions, ScopedToOwner;

    public static function additionalPermissions(): array
    {
        return ['view_team'];
    }

    // Optional: override default actions
    // protected static array $permissionActions = ['view', 'create', 'edit', 'delete', 'export', 'assign'];

    protected $fillable = ['title', 'status', 'assigned_to', 'value'];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
