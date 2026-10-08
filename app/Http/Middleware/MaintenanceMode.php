<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $setting = Setting::first();

        if ($setting && $setting->maintenance_mode) {

            // Admin panel allow
            if ($request->is('admin/*')) {
                return $next($request);
            }

            // Settings API allow
            if ($request->is('api/v1/settings')) {
                return $next($request);
            }

            // All APIs block
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'maintenance' => true,
                    'message' => $setting->maintenance_message
                ], 503);
            }

            // Web pages
            return response()->view('maintenance', [
                'message' => $setting->maintenance_message
            ], 503);
        }

        return $next($request);
    }
}