@extends('layouts.app')

@section('title', 'Set New Password — Executive Portal')

@section('content')
<section class="wrap page" style="max-width: 490px; margin: auto;">
    <div class="eyebrow">Credential Update</div>
    <h2>Set New Password</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Choose a strong password to secure your executive reader profile.
    </p>

    @if ($errors->any())
        <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Official Email Address
            </label>
            <input 
                id="email" 
                class="field" 
                type="email" 
                name="email" 
                value="{{ old('email', $email) }}" 
                required 
                autofocus
                style="width: 100%;"
            >
        </div>

        <div>
            <label for="password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                New Password <small style="font-weight: 400; color: var(--muted);">(Min. 8 characters, letters & numbers)</small>
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

        <div>
            <label for="password_confirmation" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Confirm New Password
            </label>
            <input 
                id="password_confirmation" 
                class="field" 
                type="password" 
                name="password_confirmation" 
                placeholder="••••••••••••" 
                required 
                style="width: 100%;"
            >
        </div>

        <button type="submit" class="gold" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 10px;">
            Update Password & Access Portal &rarr;
        </button>
    </form>
</section>
@endsection
