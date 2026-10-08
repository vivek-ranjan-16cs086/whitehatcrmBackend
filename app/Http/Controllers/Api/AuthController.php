<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserResource;

class AuthController extends BaseController 
{
	  	
	public function signIn2(Request $request): JsonResponse
	{ 
		try {
			$request->validate([
				'email' => ['required', 'email'],
				'password' => ['required'],
			]);

			if (!$token = Auth::guard('api')->attempt($request->only('email', 'password'))) {
				return $this->sendError(
					'Invalid email or password',
					['email' => ['These credentials do not match our records.']],
					401
				);
			}

			$user = Auth::guard('api')->user()->load('roles'); 

			return $this->sendResponse('Successfully signed in', [
				'access_token' => $token,
				'token_type' => 'Bearer',
				'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
				'user' => new UserResource($user)
			]);

		} catch (ValidationException $e) {
			return $this->sendError('Validation failed', $e->errors(), 422);
		}
	} 
	
	public function signIn(Request $request): JsonResponse
    {
        try {
    
            $request->validate([
                'email' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);
    
            $login = trim($request->email);
    
            $field = filter_var($login, FILTER_VALIDATE_EMAIL)
                ? 'email'
                : 'username';
    
            if (!$token = Auth::guard('api')->attempt([
                $field => $login,
                'password' => $request->password,
            ])) {
    
                return $this->sendError(
                    'Invalid email/username or password',
                    [
                        'email' => [
                            'These credentials do not match our records.'
                        ]
                    ],
                    401
                );
            }
    
            $user = Auth::guard('api')
                ->user()
                ->load('roles');
    
            return $this->sendResponse(
                'Successfully signed in',
                [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' =>
                        Auth::guard('api')->factory()->getTTL() * 60,
                    'user' => new UserResource($user),
                ]
            );
    
        } catch (ValidationException $e) {
    
            return $this->sendError(
                'Validation failed',
                $e->errors(),
                422
            );
        }
    }

    public function signUp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => ['required', 'string'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'min:8'],
                'confirmPass' => ['required', 'same:password'],
                'checked' => ['required', 'boolean'],
            ]);

            if (!$request->checked) {
                return $this->sendError(
                    'You must accept terms and conditions',
                    ['checked' => ['Please accept terms']],
                    422
                );
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = Auth::guard('api')->login($user);

            return $this->sendResponse('Account created successfully', [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
            ], 201);

        } catch (ValidationException $e) {
            return $this->sendError('Validation failed', $e->errors(), 422);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            return $this->sendResponse('Token refreshed', [
                'token' => Auth::guard('api')->refresh(),
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Error refreshing token', $e->getMessage(), 401);
        }
    }

	
	public function user(): JsonResponse
	{
		$user = auth('api')->user()->load('roles'); //important

		return $this->sendResponse('User data', new UserResource($user));
	}

    public function logout(): JsonResponse
    {
        try {
            Auth::guard('api')->logout();
            return $this->sendResponse('Successfully logged out');
        } catch (\Exception $e) {
            return $this->sendError('Logout failed', $e->getMessage(), 500);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $otp = rand(100000, 999999);
        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'otp' => $otp,
                'created_at' => Carbon::now()
            ]
        );

        Mail::raw("Your OTP is: $otp", function ($message) use ($request) {
            $message->to($request->email)->subject('Password Reset OTP');
        });

        return $this->sendResponse('OTP sent to email', [
            'reset_token' => $token
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            return $this->sendError('Invalid OTP', null, 400);
        }

        return $this->sendResponse('OTP verified');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->reset_token)
            ->first();

        if (!$record) {
            return $this->sendError('Invalid reset token', null, 400);
        }

        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->sendResponse('Password reset successfully');
    }

	
	public function checkEmail(Request $request): JsonResponse
	{
		try {
			$request->validate([
				'email' => ['required', 'email']
			]);

			$exists = User::where('email', $request->email)->exists();

			if (!$exists) {
				return response()->json([
					'status'  => false,
					'message' => 'Email does not exist'
				], 404);
			}

			return response()->json([
				'status'  => true,
				'message' => 'Email exists'
			]);

		} catch (ValidationException $e) {
			return response()->json([
				'status'  => false,
				'message' => 'Validation failed',
				'errors'  => $e->errors()
			], 422);
		}
	}
	
	public function updateProfile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:10',
                'alternate_phone' => 'nullable|string|max:10',
                'date_of_birth' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $user = auth('api')->user();

            //Direct data
            $updateData = $request->only([
                'name',
                'phone',
                'alternate_phone',
                'date_of_birth',
                'address'
            ]);

            //null values removal
            $updateData = array_filter($updateData, function ($value) {
                return !is_null($value) && $value !== '';
            });

            // Profile pic upload
            if ($request->hasFile('profile_pic')) {
                if ($user->profile_pic && file_exists(public_path('storage/' . $user->profile_pic))) {
                    unlink(public_path('storage/' . $user->profile_pic));
                }

                $path = $request->file('profile_pic')->store('profile_pics', 'public');
                $updateData['profile_pic'] = $path;
            }

            // MAIN FIX
            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // fresh data
            $user->refresh();

            return $this->sendResponse('Profile updated successfully', [
                'id' => $user->id,
                'name' => $user->name ?? 'Not provided',
                'email' => $user->email ?? 'Not provided',
                'phone' => $user->phone ?? 'Not provided',
                'profile_pic' => $user->profile_pic ? url('storage/' . $user->profile_pic) : 'Not provided',
                'alternate_phone' => $user->alternate_phone ?? 'Not provided',
                'date_of_birth' => $user->date_of_birth ?? 'Not provided',
                'address' => $user->address ?? 'Not provided',
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }
	
	public function storeDeviceId(Request $request)
	{
		try {
			$user = auth()->user(); 
             
			$request->validate([
              'deviceToken' => 'required|string|max:255'
            ]); 
			// device token
			$user->device_token = $request->deviceToken;
			$user->save();

			return $this->sendResponse('Device token stored successfully', [
				'user_id' => $user->id,
				'device_token' => $user->device_token
			]);

		} catch (\Exception $e) {

			\Log::error('Store Device Token Error: '.$e->getMessage());

			return $this->sendError(
				'Something went wrong',
				['error' => $e->getMessage()],
				500
			);
		}
	}
	
	public function changeStatus(Request $request, $id)
	{
		$request->validate([
			'enable' => 'required|in:0,1'
		]);

		$user = User::find($id);

		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'User not found'
			], 404);
		}

		$user->update([
			'enable' => $request->enable
		]);

		return response()->json([
			'status' => true,
			'message' => $request->enable
				? 'User activated successfully'
				: 'User deactivated successfully',
			'data' => $user
		]);
	}
}