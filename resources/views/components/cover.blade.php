@props(['book', 'size' => 'normal'])

@php
    $isLarge = ($size === 'large');
    $titleSize = $isLarge ? 'clamp(24px, 3vw, 32px)' : '20px';
    $badgeText = 'EXECUTIVE E-LIBRARY · CURATED';
    $coverUrl = $book->cover_url;
@endphp

<div {{ $attributes->class(['cover', 'cover-large' => $isLarge]) }} style="{{ $isLarge ? 'height: 410px;' : '' }} background: {{ $book->cover_bg ?? '#102946' }}; color: {{ $book->cover_fg ?? '#ffffff' }};" x-data="{ failed: false }">
    <small>{{ $badgeText }}</small>
    <b style="font-size: {{ $titleSize }};">{{ $book->title }}</b>
    <small>{{ $book->author_name }}</small>
    @if($coverUrl)
        <img src="{{ $coverUrl }}" alt="Cover of {{ $book->title }}" width="400" height="600" loading="lazy" decoding="async" x-show="!failed" x-on:error="failed = true">
    @endif
</div>
