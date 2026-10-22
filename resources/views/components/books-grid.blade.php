@props(['books'])

@if($books->count() > 0)
    <div class="books">
        @foreach($books as $book)
            <x-book-card :book="$book" />
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
