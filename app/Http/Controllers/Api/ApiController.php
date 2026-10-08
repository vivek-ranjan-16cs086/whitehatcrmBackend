<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Services\UserService;
use App\Http\Resources\UserResource; 
use App\Models\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class ApiController extends BaseController
{
	
	protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
	
	// function for Get sidebar
    public function getSidebar(Request $request)
    {
        try {
            $user = auth('api')->user();

            $role = $user->getRoleNames()->first();

            // Role based menus mapping
            $roleMenus = [

                'super-admin' => [
                    'Home',
                    'User Management',
                    'Follow-ups',
                    'Leads Management',
                    'Project Management',
                    'Target',
                    'Privacy Policy',
                    'Logout'
                ],
                
                'admin' => [
                    'Home',
                    'User Management',
                    'Follow-ups',
                    'Leads Management',
                    'Project Management',
                    'Target',
                    'Privacy Policy',
                    'Logout'
                ],

                'salesperson' => [
                    'Dashboard',
                    'Follow-ups',
                    'Leads Management',
                    'Target',
                    'Privacy Policy',
                    'Logout'
                ],
            ];

            // fallback if role not matched
            $menus = $roleMenus[$role] ?? [];

            return $this->sendResponse(
                
                'Sidebar menu fetched successfully',
                ['menus' => $menus]
            );

        } catch (Exception $e) {
            return $this->sendError('Token is invalid or expired', [], 401);
        }
    }
	
	// function for get users roles
	public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::all()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            });

            return $this->sendResponse('Roles retrieved successfully', $roles);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve roles', $e->getMessage(), 500);
        }
    }
	
	public function createUser(Request $request): JsonResponse
	{
		try {

			$request->validate([
				'email' => ['required', 'email', 'unique:users,email'],
				'name' => ['required', 'min:3'],
				'password' => ['required', 'min:8'],
				'role_id' => ['required', 'exists:roles,id'],
			]);

			// generate username with email 
			$username = strtolower(strtok($request->email, '@'));

			// if user already existed then it will add number
			$originalUsername = $username;
			$count = 1;

			while (User::where('username', $username)->exists()) {
				$username = $originalUsername . $count;
				$count++;
			}

			// User create
			$user = User::create([
				'username' => $username,
				'email' => $request->email,
				'name'=> $request->name,
				'password' => Hash::make($request->password),
			]);

			// Assign role
			$role = Role::find($request->role_id);
			$user->assignRole($role);

			return $this->sendResponse('User created successfully', [
				'user' => new UserResource($user->load('roles')),
				'role' => $role->name,
			], 201);

		} catch (ValidationException $e) {
			return $this->sendError('Validation failed', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', [
				'error' => $e->getMessage()
			], 500);
		}
	}
	
	// function for create new user
	public function createUser1(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'min:8'],
                'role_id' => ['required', 'exists:roles,id'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign role
            $role = Role::find($request->role_id);  
            $user->assignRole($role);

            return $this->sendResponse('User created successfully', [
                'user' => $user,
                'role' => $role->name
            ], 201);

        } catch (ValidationException $e) {
            return $this->sendError('Validation failed', $e->errors(), 422);
        }
    }
	
	// function for get user by id 
	public function getUser($id): JsonResponse
    {
        try {
            $user = User::with('roles')->find($id);

            $userData = [
                'id' => $user->id,
				'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return $this->sendResponse('User retrieved successfully', $userData);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve user', $e->getMessage(), 500);
        }
    }  
	
	// function for update user by id
	public function updateUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            $request->validate([
                'email' => ['email', 'unique:users,email,' . $id],
                'password' => ['min:8'],
                'role_id' => ['exists:roles,id'],
            ]);

            $updateData = [];

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Update role if provided
            if ($request->has('role_id')) {
                $role = Role::find($request->role_id);
                $user->syncRoles([$role]);
            }

            // Refresh user data
            $user->refresh();

            $userData = [
                'id' => $user->id,
                'email' => $user->email,
				'name' => $user->name,
                'roles' => $user->roles->pluck('name'),
                'updated_at' => $user->updated_at,
            ];

            return $this->sendResponse('User updated successfully', $userData);

        } catch (ValidationException $e) {
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Failed to update user', $e->getMessage(), 500);
        }
    }
	
	//function for delete user by id 
	public function deleteUser($id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->sendError('User not found', null, 404);
            }

            // Prevent Super Admin from deleting themselves
            if ($user->hasRole('Super Admin') && $user->id === auth('api')->id()) {
                return $this->sendError('Cannot delete your own Super Admin account', null, 403);
            }

            $user->delete();

            return $this->sendResponse('User deleted successfully');

        } catch (\Exception $e) {
            return $this->sendError('Failed to delete user', $e->getMessage(), 500);
        }
    }
	
	// function for get users	
	public function getUsers(Request $request): JsonResponse
	{
		try {
			$pageId = $request->page ?? 1;
			$perPage = 10;

			$query = $this->userService->getUsers($request)->where('enable', 1); 
			 
             $search = $request->input('search.value') ?? $request->input('search');

				if (!empty($search)) {
					$query->where(function ($q) use ($search) {
						$q->where('name', 'LIKE', "%{$search}%");
					});
				}

				// User Id Filter
				if ($request->filled('user_id')) {
					$query->where('id', $request->user_id);
				}
			$totalCount = $query->count();

			$users = $query->latest()->paginate($perPage, ['*'], 'page', $pageId);
			
         
			return response()->json([
				'status' => true,
				'message' => 'Users fetched successfully',

				'data' => UserResource::collection($users),

				'total_count' => $totalCount,
				'last_page' => $users->lastPage(),
				'current_page' => $users->currentPage(), 
				'per_page' => $users->perPage(),
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Failed to fetch users',
				'error' => $e->getMessage(),
			], 500);
		}
	}
	
	// function for get all users 
	public function getAllUsers(Request $request): JsonResponse
	{ 
		try {
			$pageId = $request->page ?? 1;
			$perPage = 10;

			$query = $this->userService->getUsers($request);
			
			if ($request->filled('user_id')) {
				$query->where('id', $request->user_id);
			}

			$totalCount = $query->count();

			$users = $query->latest()->paginate($perPage, ['*'], 'page', $pageId);

			return response()->json([
				'status' => true,
				'message' => 'Users fetched successfully',

				'data' => UserResource::collection($users),

				'total_count' => $totalCount,
				'last_page' => $users->lastPage(),
				'current_page' => $users->currentPage(), 
				'per_page' => $users->perPage(),
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Failed to fetch users',
				'error' => $e->getMessage(),
			], 500);
		}
	}

    //function for get notifications
    public function getNotifications(Request $request)
	{
		$user = auth()->user(); 

		$query = Notification::where('user_id', $user->id);

		// filter for read notification
		if ($request->has('is_read')) {
			$query->where('is_read', $request->is_read);
		}
        $defaultImage = url('image/no-spam.png');
		$notifications = $query->latest()
			->get()
			->map(function ($item) use ($defaultImage) {
				return [
					'id' => $item->id,
					'title' => $item->title,
					'message' => $item->message,
					'type' => $item->type,
					'data' => $item->data,
					'is_read' => $item->is_read,
					'image' => $defaultImage,
					'time' => $item->created_at->diffForHumans()
				];
			});

		// unread count 
		$unreadCount = Notification::where('user_id', $user->id)
			->where('is_read', false)
			->count();

		return response()->json([
			'status' => true,
			'unread_count' => $unreadCount,
			'data' => $notifications
		]);
	}	
	
	// function for mark as read notification
	public function markAsRead($id)
	{
		$user = auth()->user();

		$notification = Notification::where('id', $id)
			->where('user_id', $user->id)
			->first();

		if (!$notification) {
			return response()->json([
				'status' => false,
				'message' => 'Not found'
			]);
		}

		$notification->update(['is_read' => true]);

		return response()->json([
			'status' => true,
			'message' => 'Marked as read'
		]);
	}
	
	public function deleteNotification($id)
	{
		$user = auth()->user();

		$notification = Notification::where('id', $id)
			->where('user_id', $user->id)
			->first();

		if (!$notification) {
			return response()->json([
				'status' => false,
				'message' => 'Notification not found' 
			]);
		}

		$notification->delete();

		return response()->json([
			'status' => true,
			'message' => 'Notification deleted successfully'
		]);
	}
	
	public function clearAllNotifications()
	{
		$user = auth()->user();

		Notification::where('user_id', $user->id)->delete();

		return response()->json([
			'status' => true,
			'message' => 'All notifications cleared'
		]);
	} 
	
	public function notificationCount()
	{
		$user = auth()->user();

		$unreadCount = Notification::where('user_id', $user->id)
			->where('is_read', false)
			->count();

		return response()->json([
			'status' => true,
			'unread_count' => $unreadCount
		]);
	}
	
	public function heartbeat(Request $request) 
	{
		$user = auth()->user();
		$user->last_seen_at = now();
		$user->save();

		return response()->json([
			'success' => true,
		]);
	}
}