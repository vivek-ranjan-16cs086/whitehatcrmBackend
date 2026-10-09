<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiLeadPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user('api');

        if (!$user instanceof User) {
            return response()->json([
                'status' => false,
                'message' => 'Authentication required.',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        if ($user->hasRole('super-admin', 'web')) {
            return $next($request);
        }

        $permissionRecord = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->first();

        if (!$permissionRecord || !$user->hasPermissionTo($permissionRecord)) {
            return response()->json([
                'status' => false,
                'message' => "You do not have permission to {$permission}.",
                'error' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
