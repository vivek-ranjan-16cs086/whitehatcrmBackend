<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiLeadViewPermission
{
    public function handle(Request $request, Closure $next): Response
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

        $permission = Permission::query()
            ->where('name', 'view.lead')
            ->where('guard_name', 'web')
            ->first();

        if (!$permission || !$user->hasPermissionTo($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to view leads.',
                'error' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
