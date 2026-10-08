<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    protected string $permissionPrefix;

    public function viewAny(User $user): bool
    {
        return $user->can("view.{$this->permissionPrefix}");
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can("view.{$this->permissionPrefix}");
    }

    public function create(User $user): bool
    {
        return $user->can("create.{$this->permissionPrefix}");
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can("edit.{$this->permissionPrefix}");
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can("delete.{$this->permissionPrefix}");
    }
}
