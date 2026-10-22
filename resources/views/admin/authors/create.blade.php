@extends('layouts.admin')

@section('title', 'Add Author')
@section('header_title', 'Add Contributor')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.authors.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Authors</a>
</div>

<div class="admin-card" style="max-width: 650px;">
    <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Author Details</h3>

    <form action="{{ route('admin.authors.store') }}" method="POST">
        @csrf
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Full Name <span style="color:#c53030;">*</span>
                </label>
                <input type="text" id="name" name="name" class="field" value="{{ old('name') }}" placeholder="e.g. Peter F. Drucker" required style="width: 100%;">
            </div>

            <div>
                <label for="bio" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Biographical Abstract
                </label>
                <textarea id="bio" name="bio" rows="4" class="field" placeholder="Distinguished educator and father of modern management..." style="width: 100%; line-height: 1.5;">{{ old('bio') }}</textarea>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 10px 20px; font-size: 13.5px;">
                    Save Author &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
