@props(['book'])

<a class="book" href="{{ route('books.show', $book->slug) }}">
    <x-cover :book="$book" />
    <div class="book-card-meta">
        @if($book->publication_type && $book->publication_type !== 'book')
            <span class="pub-type-badge-sm">{{ $book->formattedPublicationType() }}</span>
        @endif
        @if($book->publication_year)
            <span class="pub-year-badge-sm">{{ $book->publication_year }}</span>
        @endif
    </div>
    <h3 title="{{ $book->title }}">{{ $book->title }}</h3>
    <small class="book-author" title="{{ $book->author_name }}">{{ $book->author_name }}</small>
</a>
