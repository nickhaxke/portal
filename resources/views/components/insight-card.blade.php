@props(['insight'])

<a href="{{ route('insights.show', $insight->slug) }}" class="insight">
    <div class="insight-art" x-data="{ failed: false }">
        <x-icon :name="$insight->icon_name" size="40" />
        @if($insight->featured_image)
            <img src="{{ $insight->featuredImageUrl() }}" alt="{{ $insight->image_alt ?: $insight->title }}"
                width="720" height="450" loading="lazy" decoding="async" x-show="!failed" x-on:error="failed = true">
        @endif
    </div>
    <div class="insight-body">
        <div class="eyebrow">{{ $insight->category_name }}</div>
        <h3>{{ $insight->title }}</h3>
        @if($insight->displayExcerpt())<p>{{ $insight->displayExcerpt() }}</p>@endif
        <small>{{ $insight->read_time }} &middot; <span>Read insight &rarr;</span></small>
    </div>
</a>
