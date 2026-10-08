<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function maintenanceMode()
    {
        $setting = Setting::first();

        if (!$setting) {

            return response()->json([
                'success' => false,
                'message' => 'Settings not found.'
            ],404);

        }

        return response()->json([
            'success' => true,
            'data' => [
                'maintenance_mode' => $setting->maintenance_mode,
                'maintenance_message' => $setting->maintenance_message,
            ]
        ]);
    }
}