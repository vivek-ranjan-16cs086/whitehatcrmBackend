<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Services\UserService;

class UserController extends Controller
{
	protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
		$this->middleware('auth');
    }
	

    public function index(Request $request)
	{
		if ($request->ajax()) {

			$draw = $request->draw;
			$start = $request->start;
			$length = $request->length;

			$columnIndex = $request->order[0]['column'] ?? 0;
			$columnSortOrder = $request->order[0]['dir'] ?? 'desc';
			$columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'], true)
				? $columnSortOrder
				: 'desc';

			//  SERVICE CALL
			$query = $this->userService->getUsers($request);
			
			$totalRecords = \App\Models\User::count();
			$totalFiltered = $query->count();

			// COLUMN MAP
			$columns = ['id', 'name', 'email', 'role', 'created_at'];

			if (isset($columns[$columnIndex]) && in_array($columns[$columnIndex], ['id', 'name', 'email', 'created_at'], true)) {
				$query->reorder()->orderBy($columns[$columnIndex], $columnSortOrder);
			} else {
				$query->reorder()->latest();
			}

			// PAGINATION
			$records = $query->skip($start)->take($length ?: 10)->get();

			$data = [];
			$sno = $start + 1;

			foreach ($records as $user) {

				$roleName = $user->roles->first()?->name ?? 'None';
				$id = base64_encode($user->id);

				$data[] = [
					'serialNo' => $sno++,
					'id'	   => $id,
					'name'	   => $user->name,
					'email'    => $user->email,
					'role'     => $roleName,
					'created_at' => date('d M Y', strtotime($user->created_at)),
					'action'   => view('admin.users.partials.actions', compact('user'))->render(),
				];
			}
			
			$perPage = $length ?: 10;
			$currentPage = ($start / $perPage) + 1;
			$lastPage = ceil($totalFiltered / $perPage);
			return response()->json([
				"draw" => intval($draw),
				"recordsTotal" => $totalRecords,
				"recordsFiltered" => $totalFiltered,
				"data" => $data,
				"current_page" => $currentPage,
				"last_page" => $lastPage,
			]);
		}

		return view('admin.users.index');
	}

	public function create()
    {
        return redirect()->route('admin.users.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:8|confirmed',
            'role'                  => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->syncRoles($request->role);
		
        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => "User {$user->name} created successfully.",
                'user' => $user
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

	
	public function edit(User $user)
    {
        return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        //return redirect()->route('admin.users.edit', $user);
		return redirect()->route('admin.users.index');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,{$user->id}',
            'password' => 'nullable|min:8|confirmed',
            'role'     => 'required|exists:roles,name',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);
		if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => "User {$user->name} updated successfully.",
                'user' => $user
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(User $user)
	{
		// Super admin check
		if ($user->hasRole('super-admin')) {

			if (request()->ajax()) {
				return response()->json([
					'status' => false,
					'message' => 'Cannot delete the Super Admin user.'
				]);
			}

			return redirect()->route('admin.users.index')
				->with('error', 'Cannot delete the Super Admin user.');
		}

		$user->delete();

		//  AJAX response
		if (request()->ajax()) {
			return response()->json([
				'status' => true,
				'message' => 'User deleted successfully.'
			]);
		}

		// Normal response
		return redirect()->route('admin.users.index')
			->with('success', 'User deleted successfully.');
	}
	
	public function checkUsername(Request $request)
    {
        $username = strtolower(trim($request->username));
    
        $query = User::where('username', $username);
    
        if ($request->filled('user_id')) {
            $query->where('id', '!=', $request->user_id);
        }
    
        return response()->json([
            'available' => !$query->exists()
        ]);
    }
}
