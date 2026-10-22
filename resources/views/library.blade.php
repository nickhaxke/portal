@extends('layouts.app')

@section('title', 'My Library — Executive Reader Workspace')

@section('content')
<section class="wrap page">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div class="eyebrow">Personal workspace</div>
            <h2>Executive Reader Library</h2>
            <p style="color: var(--muted); margin: 0;">
                Track your active reading progress, curated publications, and personal study bookmarks.
            </p>
        </div>

        @guest
            <div style="background: #fffdf5; border: 1px solid #fae8b8; padding: 10px 16px; border-radius: 6px; font-size: 13px; color: var(--navy); display: flex; align-items: center; gap: 8px;">
                <span style="color:var(--gold); display:inline-flex; align-items:center;"><x-icon name="insights" size="16" /></span> 
                <a href="{{ route('login') }}" style="color: var(--navy); font-weight: 700; text-decoration: underline;">Sign In</a> 
                or 
                <a href="{{ route('register') }}" style="color: var(--gold); font-weight: 700; text-decoration: underline;">Register</a> 
                to sync reading progress across devices.
            </div>
        @endguest
    </div>

    <!-- 1. Continue Reading Shelf -->
    @if(isset($readingProgress) && $readingProgress->isNotEmpty())
        <div style="margin-bottom: 40px; background: #ffffff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; box-shadow: 0 4px 18px rgba(16,41,70,0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font: 700 20px 'Playfair Display', serif; color: var(--navy); margin: 0;">
                    Continue Reading
                </h3>
                <span style="font-size: 12px; color: var(--gold); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                    Active Progress ({{ $readingProgress->count() }})
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 18px;">
                @foreach($readingProgress as $progress)
                    @if($progress->book)
                        <div style="display: flex; gap: 16px; border: 1px solid #edf1f6; border-radius: 8px; padding: 16px; background: #fafaf9; align-items: center;">
                            <!-- Cover thumbnail -->
                            <div 
                                style="width: 55px; height: 80px; flex-shrink: 0; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; font-size: 8px; font-weight: 700; padding: 4px; color: {{ $progress->book->cover_fg ?? '#fff' }}; background: {{ $progress->book->cover_bg ?? '#102946' }}; {{ $progress->book->cover_image ? 'background-image: url('.$progress->book->cover_url.'); background-size: cover;' : '' }}"
                            >
                                @if(!$progress->book->cover_image)
                                    <span style="font-size: 10px; line-height: 1.1;">{{ Str::limit($progress->book->title, 20) }}</span>
                                @endif
                            </div>

                            <!-- Details & Progress Bar -->
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="font-size: 15px; font-weight: 700; margin: 0 0 4px; color: var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <a href="{{ route('books.show', $progress->book->slug) }}" style="color: inherit; text-decoration: none;">
                                        {{ $progress->book->title }}
                                    </a>
                                </h4>
                                <small style="display: block; color: var(--muted); font-size: 12px; margin-bottom: 8px;">
                                    By {{ $progress->book->author_name }}
                                </small>

                                <!-- Visual Progress bar -->
                                <div style="background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; margin-bottom: 8px;">
                                    <div style="background: var(--gold); height: 100%; width: {{ max(5, min(100, $progress->percent_complete)) }}%; border-radius: 3px;"></div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
                                    <span style="color: var(--muted); font-weight: 600;">
                                        Page {{ $progress->current_page }} of {{ $progress->total_pages }} ({{ round($progress->percent_complete) }}%)
                                    </span>
                                    <a href="{{ route('books.reader', $progress->book->slug) }}" class="gold" style="padding: 4px 10px; font-size: 11px; text-decoration: none;">
                                        Resume &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- 2. Personal Saved Queue -->
    <div style="margin-bottom: 30px;">
        <h3 style="font: 700 20px 'Playfair Display', serif; color: var(--navy); margin: 0 0 16px;">
            Saved Publications
        </h3>

        @if($books->count() > 0)
            <x-books-grid :books="$books" />
            <div style="margin-top: 40px;">
                {{ $books->links() }}
            </div>
        @else
            <div class="empty">
                <div style="margin-bottom: 15px; color: var(--gold); display:flex; justify-content:center;"><x-icon name="bookmark" size="48" /></div>
                <h4 style="font: 700 22px 'Playfair Display', serif; color: var(--navy); margin-bottom: 8px;">Your saved queue is empty</h4>
                <p style="color: var(--muted); max-width: 400px; margin: 0 auto 25px;">Bookmark books, research papers, and reports from the catalogue to curate your lifelong executive syllabus.</p>
                <a href="{{ route('explore') }}" class="gold">Explore Repository &rarr;</a>
            </div>
        @endif
    </div>

    <!-- 3. Executive Bookmarks -->
    @if(isset($bookmarks) && $bookmarks->isNotEmpty())
        <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; box-shadow: 0 4px 18px rgba(16,41,70,0.04);">
            <h3 style="font: 700 20px 'Playfair Display', serif; color: var(--navy); margin: 0 0 16px;">
                Personal Study Bookmarks
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 14px;">
                @foreach($bookmarks as $bm)
                    @if($bm->book)
                        <div style="border: 1px solid #edf1f6; border-radius: 6px; padding: 12px 16px; background: #fafaf9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: var(--navy); display: block;">
                                    {{ $bm->book->title }}
                                </strong>
                                <small style="color: var(--gold); font-weight: 600;">Page {{ $bm->page_number }}</small>
                                @if($bm->title && $bm->title !== 'Page '.$bm->page_number)
                                    <span style="font-size: 12px; color: var(--muted);"> &middot; {{ $bm->title }}</span>
                                @endif
                            </div>
                            <a href="{{ route('books.reader', $bm->book->slug) }}" class="outline" style="padding: 4px 8px; font-size: 11px;">
                                Open Page ↗
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</section>
@endsection
