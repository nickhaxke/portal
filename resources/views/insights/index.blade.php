@extends('layouts.app')

@section('title', 'Executive Insights & Editorial Thinking')

@section('content')
<section class="wrap page">
    <div class="section-head">
        <div>
            <div class="eyebrow">Curated thinking</div>
            <h2>Executive Insights</h2>
            <p style="color: var(--muted); margin-top: 6px;">Thought leadership, strategic commentary and modern management analyses.</p>
        </div>
        <a href="{{ route('explore') }}" class="link">Explore books &rarr;</a>
    </div>

    <div class="insights">
        @forelse($insights as $insight)
            <x-insight-card :insight="$insight" />
        @empty
            <div class="empty">New executive insights will appear here when published.</div>
        @endforelse
    </div>

    <div style="margin-top: 40px;">
        {{ $insights->links() }}
    </div>
</section>
@endsection
