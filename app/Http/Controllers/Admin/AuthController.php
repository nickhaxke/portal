<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show administrator / reader login view.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('library');
        }

        return view('auth.login');
    }

    /**
     * Authenticate executive administrator or authorized reader.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many authentication attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            if (Auth::user()->isAdmin()) {
                $intended = session()->get('url.intended');
                if ($intended && str_contains($intended, '/admin')) {
                    return redirect()->intended(route('admin.dashboard'))
                        ->with('success', 'Welcome back, '.Auth::user()->name);
                }
                session()->forget('url.intended');

                return redirect()->route('admin.dashboard')
                    ->with('success', 'Welcome back, '.Auth::user()->name);
            }

            return redirect()->intended(route('library'))
                ->with('success', 'Welcome back to your Executive Library, '.Auth::user()->name);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our executive records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out safely, invalidating session and regenerating CSRF token.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been safely signed out.');
    }
}
