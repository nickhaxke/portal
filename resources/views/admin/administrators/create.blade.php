@extends('layouts.admin')

@section('title', 'Add Administrator')
@section('header_title', 'Create Administrator Account')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.administrators.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Administrators</a>
</div>

<div class="admin-card" style="max-width: 650px;">
    <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Administrator Details</h3>

    <form action="{{ route('admin.administrators.store') }}" method="POST">
        @csrf
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Staff Full Name <span style="color:#c53030;">*</span>
                </label>
                <input type="text" id="name" name="name" class="field" value="{{ old('name') }}" placeholder="e.g. Sarah Jenkins" required style="width: 100%;">
            </div>

            <div>
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Corporate / Official Email <span style="color:#c53030;">*</span>
                </label>
                <input type="email" id="email" name="email" class="field" value="{{ old('email') }}" placeholder="sarah@executive.portal" required style="width: 100%;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label for="role" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Access Role & Authority <span style="color:#c53030;">*</span>
                    </label>
                    <select id="role" name="role" class="field" required style="width: 100%;">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="position_title" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Official Position Title
                    </label>
                    <input type="text" id="position_title" name="position_title" class="field" value="{{ old('position_title', 'Curatorial Director') }}" style="width: 100%;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Password <span style="color:#c53030;">*</span>
                    </label>
                    <input type="password" id="password" name="password" class="field" required style="width: 100%;">
                </div>

                <div>
                    <label for="password_confirmation" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Confirm Password <span style="color:#c53030;">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="field" required style="width: 100%;">
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 10px 22px; font-size: 13.5px;">
                    Create Administrator &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
