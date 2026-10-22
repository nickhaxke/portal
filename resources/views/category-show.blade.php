@extends('layouts.app')

@section('title', $category->name)

@section('content')
<section class="wrap page">
    <a href="{{ route('categories.index') }}" class="plain" style="margin-bottom: 20px;">&larr; All Categories</a>
    <div style="display: flex; align-items: center; gap: 18px; margin-top: 15px; margin-bottom: 30px;">
        <div class="cat-icon" style="width: 72px; height: 72px; margin: 0; display: flex; align-items: center; justify-content: center;">
            <x-icon :name="$category->icon_name" size="36" />
        </div>
        <div>
            <div class="eyebrow">Discipline Collection</div>
            <h1 style="font: 700 36px 'Playfair Display', serif; margin: 4px 0;">{{ $category->name }}</h1>
            <p style="color: var(--muted); margin: 0;">{{ $category->description }}</p>
        </div>
    </div>

    <x-books-grid :books="$books" />

    <div style="margin-top: 40px;">
        {{ $books->links() }}
    </div>
</section>
@endsection
