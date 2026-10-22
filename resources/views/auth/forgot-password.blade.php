@extends('layouts.app')

@section('title', 'Password Recovery — Executive Portal')

@section('content')
<section class="wrap page" style="max-width: 490px; margin: auto;">
    <div class="eyebrow">Credential Recovery</div>
    <h2>Reset Password</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Enter your institutional email address and we will dispatch a secure password reset link to your verified inbox.
    </p>

    @if (session('status'))
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" style="display: flex; flex-direction: column; gap: 16px;">
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

        <button type="submit" class="gold" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 10px;">
            Send Password Reset Link &rarr;
        </button>

        <p style="text-align: center; font-size: 13px; color: var(--muted); margin-top: 12px;">
            Remember your credentials? 
            <a href="{{ route('login') }}" style="color: var(--navy); font-weight: 600; text-decoration: underline;">Return to Sign In</a>
        </p>
    </form>
</section>
@endsection
