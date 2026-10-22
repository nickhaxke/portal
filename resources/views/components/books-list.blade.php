@props(['books'])

@if($books->count() > 0)
    <div class="books-list-view">
        @foreach($books as $book)
            <article class="book-list-item">
                <a href="{{ route('books.show', $book->slug) }}" class="book-list-cover-link">
                    <x-cover :book="$book" />
                </a>

                <div class="book-list-content">
                    <div class="book-list-meta-top">
                        <span class="pub-type-badge">
                            {{ $book->formattedPublicationType() }}
                        </span>

                        @if($book->category)
                            <span class="discipline-tag">
                                <x-icon :name="$book->category->icon_name ?? 'folder'" size="12" />
                                {{ $book->category->name }}
                            </span>
                        @endif

                        @if($book->library)
                            <span class="library-tag">
                                <x-icon name="library" size="12" />
                                {{ $book->library->name }}
                            </span>
                        @endif

                        @if($book->publication_year)
                            <span class="year-tag">
                                {{ $book->publication_year }}
                            </span>
                        @endif
                    </div>

                    <h3 class="book-list-title">
                        <a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a>
                    </h3>

                    @if($book->subtitle)
                        <div class="book-list-subtitle">{{ $book->subtitle }}</div>
                    @endif

                    <div class="book-list-authors">
                        <span class="label">By</span>
                        <span class="author-name">{{ $book->author_name }}</span>
                        @if($book->publisher)
                            <span class="sep">&bull;</span>
                            <span class="publisher">{{ $book->publisher }}</span>
                        @endif
                        @if($book->edition)
                            <span class="sep">&bull;</span>
                            <span class="edition">{{ $book->edition }}</span>
                        @endif
                    </div>

                    @if($book->description)
                        <p class="book-list-desc">
                            {{ Str::limit(strip_tags($book->description), 200) }}
                        </p>
                    @endif

                    <div class="book-list-footer">
                        <div class="book-list-ids">
                            @if($book->isbn)
                                <span class="id-item"><strong>ISBN:</strong> {{ $book->isbn }}</span>
                            @endif
                            @if($book->doi)
                                <span class="id-item"><strong>DOI:</strong> {{ $book->doi }}</span>
                            @endif
                            @if($book->page_count)
                                <span class="id-item"><strong>Pages:</strong> {{ $book->page_count }}</span>
                            @endif
                        </div>

                        <div class="book-list-actions">
                            <a href="{{ route('books.show', $book->slug) }}" class="action-btn primary">
                                Read Publication &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@else
    <div class="empty">
        <p>No books match your search or filter criteria.</p>
        <button type="button" class="gold" style="margin-top: 15px; cursor: pointer; border: none; font-size: 14px; font-weight: 600;" @click="$dispatch('reset-filters')">
            Reset All Filters
        </button>
    </div>
@endif
