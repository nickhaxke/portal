@extends('layouts.app')

@section('title', 'Curated Leadership & Lifelong Learning')

@section('content')
<div class="hero">
    <div class="wrap hero-inner">
        <div class="hero-copy">
            <div class="eyebrow">Knowledge. Leadership. Impact.</div>
            <h1>Ideas Today for a <em>Better Tomorrow</em></h1>
            <p>Explore books, expert insights and trusted knowledge to help you learn, lead and make informed decisions.</p>
            <form class="search" action="{{ route('explore') }}" method="GET" role="search">
                <input type="search" name="q" placeholder="Search books, authors, topics..." aria-label="Search books, authors and topics" autocomplete="off">
                <button type="submit" class="gold">Search &rarr;</button>
            </form>
            <div class="chips">
                <span class="chips-label">Popular:</span>
                @foreach(['Leadership', 'Strategy', 'Technology', 'Personal Development'] as $chip)
                    <a href="{{ route('explore', ['q' => $chip]) }}" class="chip">{{ $chip }}</a>
                @endforeach
            </div>
        </div>
        @if(isset($heroSlides) && $heroSlides->isNotEmpty())
            <div class="hero-slider" x-data="heroSlider({{ $heroSlides->count() }})"
                role="region" aria-roledescription="carousel" aria-label="Executive highlights"
                @mouseenter="hovered = true" @mouseleave="hovered = false"
                @focusin="focused = true" @focusout="focused = $el.contains($event.relatedTarget)">
                <div class="hero-slides-container" :aria-live="playing ? 'off' : 'polite'">
                    @foreach($heroSlides as $idx => $slide)
                        <article class="hero-slide" x-show="current === {{ $idx }}" @if($idx > 0) style="display: none;" @endif
                            role="group" aria-roledescription="slide" aria-label="{{ $idx + 1 }} of {{ $heroSlides->count() }}">
                            <div class="hero-slide-image" x-data="{ failed: false }">
                                <x-icon name="collections" size="64" />
                                <img src="{{ $slide->imageUrl() }}" alt="" width="800" height="600"
                                    @if($idx === 0) fetchpriority="high" @else loading="lazy" @endif
                                    x-show="!failed" x-on:error="failed = true">
                            </div>
                            <div class="hero-slide-caption">
                                <div class="eyebrow">Executive perspectives</div>
                                <h2>{{ $slide->title }}</h2>
                                @if($slide->subtitle)<p>{{ $slide->subtitle }}</p>@endif
                                @if($slide->cta_text && $slide->cta_url)
                                    <a href="{{ $slide->safeCtaUrl() }}" class="link">{{ $slide->cta_text }} &rarr;</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                @if($heroSlides->count() > 1)
                    <div class="slider-controls">
                        <button type="button" @click="prev()" class="slider-btn" aria-label="Previous slide"><span aria-hidden="true">&larr;</span></button>
                        <div class="slider-dots">
                            @foreach($heroSlides as $idx => $slide)
                                <button type="button" @click="goTo({{ $idx }})" class="slider-dot"
                                    :class="{ 'is-active': current === {{ $idx }} }" :aria-pressed="current === {{ $idx }}"
                                    aria-label="Show slide {{ $idx + 1 }}"></button>
                            @endforeach
                        </div>
                        <button type="button" @click="next()" class="slider-btn" aria-label="Next slide"><span aria-hidden="true">&rarr;</span></button>
                        <button type="button" @click="playing = !playing" class="slider-play" :aria-label="playing ? 'Pause slideshow' : 'Play slideshow'" x-text="playing ? 'Pause' : 'Play'">Pause</button>
                    </div>
                @endif
            </div>
        @else
            <div class="hero-art" role="img" aria-label="Books on leadership, innovation, strategy, wisdom and growth">
                <div class="art-shelf">
                    <div class="spine" style="--h: 72%; --c: #172b40;">LEADERSHIP</div>
                    <div class="spine" style="--h: 90%; --c: #2d3c48;">INNOVATION</div>
                    <div class="spine" style="--h: 80%; --c: #7b4d31;">STRATEGY</div>
                    <div class="spine" style="--h: 97%; --c: #16273b;">WISDOM</div>
                    <div class="spine" style="--h: 67%; --c: #6b5039;">GROWTH</div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Stats Bar -->
<div class="wrap stats">
    <div>
        <strong>Curated</strong>
        <small>Knowledge collections ({{ $stats['books_count'] }} Titles)</small>
    </div>
    <div>
        <strong>{{ $stats['libraries_count'] ?? 1 }}</strong>
        <small>Digital Libraries</small>
    </div>
    <div>
        <strong>{{ $stats['categories_count'] }}</strong>
        <small>Explore disciplines</small>
    </div>
    <div>
        <strong>Anytime</strong>
        <small>Responsive PDF reading</small>
    </div>
</div>

<!-- Digital Libraries -->
@if(isset($libraries) && $libraries->isNotEmpty())
<section class="wrap">
    <div class="section-head">
        <div>
            <div class="eyebrow">Institutional Repositories</div>
            <h2>Curated Digital Libraries</h2>
        </div>
        <a href="{{ Route::has('libraries.index') ? route('libraries.index') : url('/libraries') }}" class="link">View all libraries &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        @foreach($libraries as $lib)
            <a href="{{ Route::has('libraries.show') ? route('libraries.show', $lib->slug) : url('/libraries/' . $lib->slug) }}" class="category" style="text-decoration: none; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; border-radius: 8px;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <span style="width: 40px; height: 40px; border-radius: 8px; background: rgba(16, 41, 70, 0.08); color: var(--navy); display: inline-flex; align-items: center; justify-content: center;">
                            <x-icon :name="$lib->icon_name" size="22" />
                        </span>
                        <span style="font-size: 11.5px; font-weight: 700; color: var(--gold); background: #fbf7ee; padding: 3px 9px; border-radius: 12px; border: 1px solid #fae8b8;">
                            {{ $lib->books_count }} {{ Str::plural('Title', $lib->books_count) }}
                        </span>
                    </div>
                    <strong style="font-family: 'Playfair Display', serif; font-size: 18px; color: var(--navy); display: block; margin-bottom: 6px;">{{ $lib->name }}</strong>
                    <p style="color: var(--muted); font-size: 13px; line-height: 1.5; margin: 0 0 12px;">{{ Str::limit($lib->description ?: 'Curated publications under institutional library governance.', 110) }}</p>
                </div>
                <small style="color: var(--gold); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">Explore Library &rarr;</small>
            </a>
        @endforeach
    </div>
</section>
@endif

<!-- Explore Categories -->
<section class="wrap">
    <div class="section-head">
        <div>
            <div class="eyebrow">Explore categories</div>
            <h2>Discover Knowledge Across Disciplines</h2>
        </div>
        <a href="{{ route('categories.index') }}" class="link">View all &rarr;</a>
    </div>

    <div class="categories">
        @foreach($categories as $cat)
            <a href="{{ route('explore', ['category' => $cat->slug]) }}" class="category">
                <div class="cat-icon">
                    <x-icon :name="$cat->icon_name" size="26" />
                </div>
                <strong>{{ $cat->name }}</strong>
                <small>{{ $cat->books_count }} {{ Str::plural('book', $cat->books_count) }} &middot; Explore collection &rarr;</small>
            </a>
        @endforeach
    </div>
</section>

<!-- Featured Books -->
<section class="wrap">
    <div class="section-head">
        <div>
            <div class="eyebrow">Featured books</div>
            <h2>Recommended for You</h2>
        </div>
        <a href="{{ route('explore') }}" class="link">View all &rarr;</a>
    </div>

    @if($featuredBooks->isNotEmpty())
        <x-books-grid :books="$featuredBooks" />
    @else
        <div class="empty empty-shelf">
            <x-icon name="publications" size="36" />
            <h3>The collection is being curated</h3>
            <p>Published recommendations will appear here as they become available.</p>
            <a href="{{ route('explore') }}" class="outline">Explore the library &rarr;</a>
        </div>
    @endif
</section>

<!-- Executive Insights -->
<section class="wrap">
    <div class="section-head">
        <div>
            <div class="eyebrow">Executive insights</div>
            <h2>Ideas for Informed Leadership</h2>
        </div>
        <a href="{{ route('insights.index') }}" class="link">View all &rarr;</a>
    </div>

    <div class="insights">
        @forelse($insights as $insight)
            <x-insight-card :insight="$insight" />
        @empty
            <p class="empty">New executive insights will appear here when published.</p>
        @endforelse
    </div>
</section>

<!-- Lifelong Learning Banner -->
<section class="wrap">
    <div class="banner">
        <div>
            <div class="eyebrow" style="color: var(--gold-light);">Lifelong learning</div>
            <h2>Start Your Knowledge Journey Today</h2>
            <p>Explore ideas that inspire better decisions and visionary leadership.</p>
        </div>
        <a href="{{ route('explore') }}" class="gold" style="background: var(--gold); white-space: nowrap;">Explore Library &rarr;</a>
    </div>
</section>

@endsection
