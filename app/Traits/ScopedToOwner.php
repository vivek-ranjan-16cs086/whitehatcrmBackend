<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedToOwner
{
    protected static function bootScopedToOwner(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            if (auth()->check() && auth()->user()->hasRole('salesperson')) {
                $builder->where('assigned_to', auth()->id());
            }
        });
    }
}
