<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();

        return view('admin.settings.index', compact('setting'));
    }

    public function update(Request $request)
	{
		$setting = Setting::first();

		if (!$setting) {
			$setting = Setting::create([
				'maintenance_mode' => false,
				'maintenance_message' => '',
			]);
		}

		$setting->update([
			'maintenance_mode' => $request->maintenance_mode,
			'maintenance_message' => $request->maintenance_message,
		]);

		return back()->with('success', 'Updated Successfully');
	}
}