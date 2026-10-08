<?php

namespace App\Traits;

trait HasModelPermissions
{
    protected static array $permissionActions = ['view', 'create', 'edit', 'delete'];

    public static function permissionPrefix(): string
    {
        return strtolower(class_basename(static::class));
    }

    public static function allPermissions(): array
    {
        $extras = method_exists(static::class, 'additionalPermissions')
            ? static::additionalPermissions()
            : [];

        $actions = array_merge(static::$permissionActions, $extras);

        return array_map(
            fn($action) => "{$action}." . static::permissionPrefix(),
            $actions
        );
    }
}
