<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }
	
	public function login(Request $request)
	{
		$credentials = $request->validate([
			'email'    => ['required', 'email'],
			'password' => ['required'],
		]);

		if (Auth::attempt($credentials, $request->boolean('remember'))) {

			$request->session()->regenerate();

			// only super admin can login
			if (!Auth::user()->hasRole('super-admin')) {

				Auth::logout();

				$request->session()->invalidate();
				$request->session()->regenerateToken();

				return back()
					->withInput($request->only('email', 'remember'))
					->withErrors([
						'email' => 'You are not authorized to access this panel.',
					]);
			}

			return redirect()->intended(route('dashboard'));
		}

		return back()
			->withInput($request->only('email', 'remember'))
			->withErrors([
				'email' => 'These credentials do not match our records.',
			]);
	}

    public function login1(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'], 
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
