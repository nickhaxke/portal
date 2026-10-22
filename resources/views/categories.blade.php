@extends('layouts.app')

@section('title', 'Knowledge Areas & Disciplines')

@section('content')
<section class="wrap page">
    <div class="eyebrow">Knowledge areas</div>
    <h2>All Categories</h2>
    <p style="color: var(--muted); margin-bottom: 30px;">Explore executive literature organized across core strategic pillars.</p>

    <div class="categories">
        @foreach($categories as $cat)
            <a href="{{ route('categories.show', $cat->slug) }}" class="category">
                <div class="cat-icon">
                    <x-icon :name="$cat->icon_name" size="28" />
                </div>
                <strong>{{ $cat->name }}</strong>
                <p style="font-size: 13px; color: var(--muted); line-height: 1.5; margin: 8px 0 12px;">{{ $cat->description }}</p>
                <small>{{ $cat->books_count }} {{ Str::plural('book', $cat->books_count) }} &middot; Explore collection &rarr;</small>
            </a>
        @endforeach
    </div>
</section>
@endsection
