<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function getUsers($request)
    {
        $query = User::query()
            ->with('roles:id,name'); // lighter eager loading

        // ⭐ Filter by role (Spatie)
        if ($request->filled('role_id')) {
            $roleId = $request->role_id;

            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('id', $roleId); 
            }); 
        }

        // Search (Select2 / DataTables format)
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Important for pagination stability
        $query->orderBy('id', 'desc');

        return $query;
    }
}