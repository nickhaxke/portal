@extends('layouts.admin')

@section('title', 'Edit: ' . $collection->name)
@section('header_title', 'Edit Curated Collection')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.collections.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Collections</a>
</div>

<div class="admin-card" style="max-width: 800px;">
    <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Edit: {{ $collection->name }}</h3>

    <form action="{{ route('admin.collections.update', $collection->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Collection Title <span style="color:#c53030;">*</span>
                </label>
                <input type="text" id="name" name="name" class="field" value="{{ old('name', $collection->name) }}" required style="width: 100%;">
            </div>

            <div>
                <label for="description" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Curatorial Summary
                </label>
                <textarea id="description" name="description" rows="3" class="field" style="width: 100%; line-height: 1.5;">{{ old('description', $collection->description) }}</textarea>
            </div>

            <div>
                <label for="book_ids" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Select Publications in this Anthology
                </label>
                <select name="book_ids[]" id="book_ids" class="field" multiple style="width: 100%; height: 160px; font-size: 13px;">
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" {{ in_array($book->id, old('book_ids', $selectedBookIds)) ? 'selected' : '' }}>
                            {{ $book->title }} — By {{ $book->author_name }} ({{ $book->category?->name ?? 'General' }})
                        </option>
                    @endforeach
                </select>
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Hold Ctrl / Cmd to adjust included publications</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div style="padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $collection->is_featured) ? 'checked' : '' }}>
                        Feature on Homepage
                    </label>
                </div>

                <div style="padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $collection->is_published) ? 'checked' : '' }}>
                        Published
                    </label>
                </div>

                <div>
                    <label style="font-size: 11.5px; font-weight: 600; color: var(--navy); display:block; margin-bottom: 2px;">Sort Order</label>
                    <input type="number" name="sort_order" class="field" value="{{ old('sort_order', $collection->sort_order) }}" min="0" style="width: 100%;">
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 10px 22px; font-size: 14px;">
                    Update Curated Collection &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
