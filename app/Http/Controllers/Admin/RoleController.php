<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = $this->groupedPermissions();
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' created successfully.");
    }

    public function edit(Role $role)
    {
        $permissions        = $this->groupedPermissions();
        $currentPermissions = $role->permissions->pluck('name')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'currentPermissions'));
    }

    public function show(Role $role)
    {
        return redirect()->route('admin.roles.edit', $role);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => "required|string|unique:roles,name,{$role->id}",
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(Role $role)
	{
		if (in_array($role->name, ['super-admin', 'admin'])) {
			return response()->json([
				'status' => false,
				'message' => "Cannot delete the '{$role->name}' role."
			]);
		}

		$role->delete();

		return response()->json([
			'status' => true,
			'message' => 'Role deleted successfully.'
		]);
	}

    private function groupedPermissions(): array
    {
        $grouped = [];
        $permissions = Permission::orderBy('name')->get();

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $model = $parts[1] ?? 'general';
            $grouped[$model][] = $permission;
        }

        return $grouped;
    }
}
