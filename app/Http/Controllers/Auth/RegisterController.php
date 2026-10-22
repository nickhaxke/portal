<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the executive reader registration form.
     */
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('library');
        }

        if (! config('portal.registration_enabled', true)) {
            return redirect()->route('login')
                ->with('error', 'New reader registration is currently by executive invitation only.');
        }

        return view('auth.register');
    }

    /**
     * Handle incoming reader registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        if (! config('portal.registration_enabled', true)) {
            return redirect()->route('login')
                ->with('error', 'New reader registration is currently closed.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'position_title' => ['required', 'string', 'max:100'],
            'institution' => ['nullable', 'string', 'max:191'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Evaluate institutional domain restrictions if configured
        $policy = config('portal.registration_policy', 'open');
        $allowedDomains = config('portal.allowed_domains', []);

        if ($policy === 'institutional_domain' && ! empty($allowedDomains)) {
            $emailParts = explode('@', $validated['email']);
            $domain = end($emailParts);
            if (! in_array(strtolower($domain), array_map('strtolower', $allowedDomains))) {
                return back()->withErrors([
                    'email' => 'Registration is restricted to authorized institutional email domains.',
                ])->withInput();
            }
        }

        $initialStatus = ($policy === 'approval_required') ? 'pending' : 'active';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'reader',
            'position_title' => $validated['position_title'],
            'institution' => $validated['institution'] ?? null,
            'status' => $initialStatus,
        ]);

        event(new Registered($user));

        if ($initialStatus === 'pending') {
            return redirect()->route('login')->with(
                'success',
                'Your registration application has been submitted for executive review. You will be notified upon activation.'
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (config('portal.require_email_verification') && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('library')
            ->with('success', "Welcome to the Executive Intelligence Portal, {$user->name}. Your reader profile is now active.");
    }
}
