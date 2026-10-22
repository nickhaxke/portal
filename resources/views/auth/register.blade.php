@extends('layouts.app')

@section('title', 'Reader Registration — Executive Access')

@section('content')
<section class="wrap page" style="max-width: 520px; margin: auto;">
    <div class="eyebrow">Institutional Reader Profile</div>
    <h2>Executive Access Application</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Register for reader access to the Executive Intelligence, Leadership & Lifelong Learning repository.
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

    <form method="POST" action="{{ route('register') }}" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf

        <div>
            <label for="name" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Full Legal / Professional Name
            </label>
            <input 
                id="name" 
                class="field" 
                type="text" 
                name="name" 
                value="{{ old('name') }}" 
                placeholder="Dr. Katherine Vance" 
                required 
                autofocus
                style="width: 100%;"
            >
        </div>

        <div>
            <label for="email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Institutional / Corporate Email Address
            </label>
            <input 
                id="email" 
                class="field" 
                type="email" 
                name="email" 
                value="{{ old('email') }}" 
                placeholder="k.vance@governance-institute.org" 
                required 
                style="width: 100%;"
            >
        </div>

        <div class="auth-columns">
            <div>
                <label for="position_title" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                    Executive Position / Title
                </label>
                <input 
                    id="position_title" 
                    class="field" 
                    type="text" 
                    name="position_title" 
                    value="{{ old('position_title') }}" 
                    placeholder="Chief Strategy Officer" 
                    required 
                    style="width: 100%;"
                >
            </div>

            <div>
                <label for="institution" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                    Institution / Organization
                </label>
                <input 
                    id="institution" 
                    class="field" 
                    type="text" 
                    name="institution" 
                    value="{{ old('institution') }}" 
                    placeholder="Global Policy Forum" 
                    style="width: 100%;"
                >
            </div>
        </div>

        <div>
            <label for="password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
                Secure Password <small style="font-weight: 400; color: var(--muted);">(Min. 8 characters, letters & numbers)</small>
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
                Confirm Password
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
            Complete Executive Registration &rarr;
        </button>

        <p style="text-align: center; font-size: 13px; color: var(--muted); margin-top: 12px;">
            Already have an active reader account? 
            <a href="{{ route('login') }}" style="color: var(--navy); font-weight: 600; text-decoration: underline;">Sign In here</a>
        </p>
    </form>
</section>
@endsection
