@extends('layouts.app')

@section('title', 'Digital Libraries — Curated Leadership Collections')

@section('content')
<section class="wrap page">
    <div class="eyebrow">Institutional Repositories</div>
    <h2>Digital Libraries</h2>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
        <p style="color: var(--muted); margin: 0; max-width: 700px;">
            Explore distinct executive libraries organized by domain, methodology, and strategic discipline — built for rigorous leadership study and lifelong learning.
        </p>
        <span style="font-weight: 600; color: var(--navy); font-size: 14px; background: var(--cream); padding: 5px 14px; border-radius: 20px; border: 1px solid var(--border);">
            {{ $libraries->count() }} {{ Str::plural('Library', $libraries->count()) }} Available
        </span>
    </div>

    <!-- Libraries Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; margin-top: 10px;">
        @forelse($libraries as $library)
            <article style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(16, 41, 70, 0.04); display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s;">
                <div style="padding: 24px; flex: 1;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <span style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 41, 70, 0.07); color: var(--navy); display: inline-flex; align-items: center; justify-content: center;">
                            <x-icon :name="$library->icon_name" size="24" />
                        </span>
                        <span style="font-size: 12px; font-weight: 700; color: var(--gold); background: #fbf7ee; padding: 4px 10px; border-radius: 20px; border: 1px solid #fae8b8;">
                            {{ $library->books_count }} {{ Str::plural('Title', $library->books_count) }}
                        </span>
                    </div>

                    <h3 style="margin: 0 0 8px; font-family: 'Playfair Display', serif; font-size: 20px; color: var(--navy);">
                        <a href="{{ route('libraries.show', $library->slug) }}" style="color: var(--navy); text-decoration: none;">
                            {{ $library->name }}
                        </a>
                    </h3>

                    <p style="color: var(--muted); font-size: 13.5px; line-height: 1.6; margin: 0 0 16px;">
                        {{ Str::limit($library->description ?: 'Dedicated executive library collection.', 140) }}
                    </p>
                </div>

                <div style="background: #fafaf8; border-top: 1px solid var(--border); padding: 14px 24px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12px; color: var(--muted);">
                        Access: <strong style="color: var(--navy); text-transform: capitalize;">{{ $library->visibility }}</strong>
                    </span>
                    <a href="{{ route('libraries.show', $library->slug) }}" class="plain" style="color: var(--gold); font-weight: 700; font-size: 13.5px; display: inline-flex; align-items: center; gap: 4px;">
                        Browse Library &rarr;
                    </a>
                </div>
            </article>
        @empty
            <div style="grid-column: 1 / -1; background: #ffffff; border: 1px dashed var(--border); border-radius: 8px; padding: 50px 20px; text-align: center;">
                <x-icon name="library" size="44" />
                <h3 style="margin: 15px 0 6px; color: var(--navy);">Digital Libraries In Preparation</h3>
                <p style="color: var(--muted); font-size: 14px; max-width: 420px; margin: 0 auto 20px;">
                    Our executive editorial board is categorizing curated collections. Explore all catalogue publications in the interim.
                </p>
                <a href="{{ route('explore') }}" class="gold" style="padding: 10px 22px; font-size: 13px; text-decoration: none; display: inline-block;">
                    Explore All Publications &rarr;
                </a>
            </div>
        @endforelse
    </div>
</section>
@endsection
