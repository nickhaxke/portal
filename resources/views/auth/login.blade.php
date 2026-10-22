@extends('layouts.app')

@section('title', 'Executive Administrator Login')

@section('content')
<section class="wrap page" style="max-width: 490px; margin: auto;" x-data="{
    fillDemo() {
        document.getElementById('email').value = 'admin@executive.portal';
        document.getElementById('password').value = 'Password123!';
    }
}">
    <div class="eyebrow">Executive portal access</div>
    <h2>Welcome Back</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">Sign in to manage the executive collection, upload publications and oversee portal operations.</p>

    <div class="notice" style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
        <div>
            <strong>Demo Credentials:</strong><br>
            <span style="font-family: monospace; font-size: 12px;">admin@executive.portal &middot; Password123!</span>
        </div>
        <button type="button" @click="fillDemo()" class="outline" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;">
            Auto-fill
        </button>
    </div>

    @if ($errors->any())
        <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf

        <div>
            <label for="email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Official Email Address
            </label>
            <input 
                id="email" 
                class="field" 
                type="email" 
                name="email" 
                value="{{ old('email') }}" 
                placeholder="director@organization.org" 
                required 
                autofocus
                style="width: 100%;"
            >
        </div>

        <div>
            <label for="password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Secure Password
            </label>
            <input 
                id="password" 
                class="field" 
                type="password" 
                name="password" 
                placeholder="••••••••••••" 
                required 
                style="width: 100%;"
            >
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--muted);">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                Remember session
            </label>
            <a href="{{ route('password.request') }}" style="color: var(--navy); font-weight: 600; text-decoration: underline;">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="gold" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 10px;">
            Sign In to Executive Portal &rarr;
        </button>

        @if(config('portal.registration_enabled', true))
            <p style="text-align: center; font-size: 13px; color: var(--muted); margin-top: 12px;">
                New Reader? 
                <a href="{{ route('register') }}" style="color: var(--navy); font-weight: 600; text-decoration: underline;">Register for Executive Access</a>
            </p>
        @endif
    </form>
</section>
@endsection
