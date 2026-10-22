@extends('layouts.app')

@section('title', $insight->title)

@section('content')
<section class="wrap page" style="max-width: 800px;">
    <a href="{{ route('insights.index') }}" class="plain">&larr; All Insights</a>
    
    <div class="eyebrow" style="margin-top: 35px;">{{ $insight->category_name }} &middot; {{ $insight->read_time }}</div>
    <h1 style="font: 700 clamp(30px, 4vw, 44px) 'Playfair Display', serif; margin: 12px 0 20px; line-height: 1.2;">{{ $insight->title }}</h1>
    
    <div class="insight-art article-image" x-data="{ failed: false }">
        <x-icon :name="$insight->icon_name" size="48" />
        @if($insight->featured_image)
            <img src="{{ $insight->featuredImageUrl() }}" alt="{{ $insight->image_alt ?: $insight->title }}" width="1200" height="675" x-show="!failed" x-on:error="failed = true">
        @endif
    </div>
    <div class="article-content">
        {!! nl2br(e($insight->content)) !!}
    </div>

    <div class="notice">
        Original executive analysis curated for institutional leadership and lifelong learning.
    </div>

    <div class="article-actions">
        <a href="{{ route('insights.index') }}" class="outline">&larr; Back to Insights</a>
        <a href="{{ route('explore') }}" class="gold">Explore Related Books &rarr;</a>
    </div>
</section>
@endsection
