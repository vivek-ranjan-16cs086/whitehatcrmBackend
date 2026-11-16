<?php

namespace App\Http\Controllers;

use App\Services\DeviceRegistrationService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function register(Request $request, DeviceRegistrationService $devices)
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:191',
            'platform' => 'required|in:android,web,ios',
            'fcm_token' => 'required|string|max:4096',
        ]);
        $device = $devices->register($request->user(), $data['device_id'], $data['platform'], $data['fcm_token']);
        if ($request->hasSession()) {
            $request->session()->put('notification_device_id', $device->device_id);
        }

        return response()->json(['status' => true, 'message' => 'Device registered successfully', 'data' => [
            'device_id' => $device->device_id,
        ]]);
    }

    public function unregister(Request $request)
    {
        $data = $request->validate(['device_id' => 'required|string|max:191']);
        $request->user()->devices()->where('device_id', $data['device_id'])->delete();

        return response()->json(['status' => true, 'message' => 'Device unregistered']);
    }
}
