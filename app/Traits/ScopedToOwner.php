<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedToOwner
{
    protected static function bootScopedToOwner(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            $model = $builder->getModel();
            $permission = 'edit.' . strtolower(class_basename($model));

            if ($user && !$user->hasCrmPermission($permission)) {
                $builder->where('assigned_to', auth()->id());
            }
        });
    }
}
