<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;

class DeviceRegistrationService
{
    public function register(User $user, string $deviceId, string $platform, string $token): UserDevice
    {
        return DB::transaction(function () use ($user, $deviceId, $platform, $token) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $hash = hash('sha256', $token);
            // One token belongs to one account and installation, including account switches.
            UserDevice::where('token_hash', $hash)->where(function ($query) use ($user, $deviceId) {
                $query->where('user_id', '!=', $user->id)->orWhere('device_id', '!=', $deviceId);
            })->delete();

            return UserDevice::updateOrCreate(
                ['user_id' => $user->id, 'device_id' => $deviceId],
                ['platform' => $platform, 'fcm_token' => $token, 'token_hash' => $hash, 'last_seen_at' => now()]
            );
        }, 3);
    }
}
