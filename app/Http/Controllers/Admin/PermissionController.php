<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        $roles       = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();

        // Group permissions by model (second segment of action.model)
        $grouped = [];
        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $model = $parts[1] ?? 'general';
            $grouped[$model][] = $permission;
        }

        // Build matrix: $matrix[$model][$roleName][$permName] = bool
        $matrix = [];
        foreach ($grouped as $model => $perms) {
            foreach ($perms as $perm) {
                foreach ($roles as $role) {
                    $matrix[$model][$role->name][$perm->name] = $role->hasPermissionTo($perm->name);
                }
            }
        }

        return view('admin.permissions.index', compact('grouped', 'roles', 'matrix'));
    }

    public function sync()
    {
        Artisan::call('permission:sync');
        Artisan::call('permission:cache-reset');

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissions synced and cache cleared successfully.');
    }
}
