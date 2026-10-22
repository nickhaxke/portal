@extends('layouts.app')

@section('title', 'Verify Email — Executive Portal')

@section('content')
<section class="wrap page" style="max-width: 520px; margin: auto; text-align: center;">
    <div style="margin-bottom: 12px; color: var(--gold); display: flex; justify-content: center;"><x-icon name="mail" size="44" /></div>
    <div class="eyebrow">Security Verification</div>
    <h2>Verify Your Email Address</h2>
    <p style="color: var(--muted); margin-bottom: 24px;">
        Thanks for joining the Executive Intelligence Portal! Before reading restricted materials, please click the verification link we dispatched to your email inbox.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; text-align: left;">
            A fresh verification link has been dispatched to the email address you specified during registration.
        </div>
    @endif

    <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
        <form method="POST" action="{{ route('verification.send') }}" style="width: 100%;">
            @csrf
            <button type="submit" class="gold" style="width: 100%; padding: 14px; font-size: 14px;">
                Resend Verification Email &rarr;
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="width: 100%;">
            @csrf
            <button type="submit" class="outline" style="width: 100%; padding: 10px; font-size: 13px;">
                Sign Out
            </button>
        </form>
    </div>
</section>
@endsection
