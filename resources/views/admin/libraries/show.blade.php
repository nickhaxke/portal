@extends('layouts.admin')

@section('title', 'Manage Library — ' . $library->name)
@section('header_title', $library->name)

@section('header_actions')
    <a href="{{ route('admin.libraries.edit', $library->id) }}" class="outline" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        Edit Library Details
    </a>
    <a href="{{ route('libraries.show', $library->slug) }}" target="_blank" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
        <span>Public View ↗</span>
    </a>
@endsection

@section('content')
<div style="margin-bottom: 25px;">
    <!-- Library Header Card -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: flex-start; gap: 16px;">
                <span style="width: 50px; height: 50px; border-radius: 10px; background: rgba(16, 41, 70, 0.08); color: var(--navy); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <x-icon :name="$library->icon_name" size="26" />
                </span>
                <div>
                    <h2 style="margin: 0 0 4px; color: var(--navy); font-size: 22px;">{{ $library->name }}</h2>
                    <p style="color: #64748b; font-size: 13.5px; margin: 0 0 10px; max-width: 650px; line-height: 1.5;">
                        {{ $library->description ?: 'No detailed description provided for this library.' }}
                    </p>
                    <div style="display: flex; align-items: center; gap: 14px; font-size: 12.5px; color: #64748b;">
                        <span>Slug: <strong style="font-family: monospace; color: var(--navy);">{{ $library->slug }}</strong></span>
                        <span>&bull;</span>
                        <span>Access: <strong style="text-transform: capitalize; color: var(--navy);">{{ $library->visibility }}</strong></span>
                        <span>&bull;</span>
                        <span>Status: <strong style="color: {{ $library->is_published ? '#117838' : '#64748b' }};">{{ $library->is_published ? 'Published' : 'Draft' }}</strong></span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 16px;">
                <div style="text-align: right; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 18px; border-radius: 6px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Total Books</span>
                    <strong style="font-size: 22px; color: var(--navy);">{{ $library->books_count }}</strong>
                </div>
                <div style="text-align: right; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 18px; border-radius: 6px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Published</span>
                    <strong style="font-size: 22px; color: var(--gold);">{{ $library->published_books_count }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Assignment Form -->
    @if(isset($availableBooks) && $availableBooks->isNotEmpty())
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 700; color: var(--navy);">Assign Publications to This Library</h3>
            <p style="color: #64748b; font-size: 12.5px; margin: 0 0 15px;">Select publications currently unassigned or in other collections to move them into {{ $library->name }}.</p>

            <form action="{{ route('admin.libraries.assign-books', $library->id) }}" method="POST" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                @csrf
                <div style="flex: 1; min-width: 300px;">
                    <select name="book_ids[]" multiple size="3" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                        @foreach($availableBooks as $availBook)
                            <option value="{{ $availBook->id }}">
                                {{ $availBook->title }} (by {{ $availBook->author_name }}) — Current: {{ $availBook->library?->name ?? 'Unassigned' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="gold" style="padding: 10px 18px; font-size: 13px; cursor: pointer; align-self: flex-start;">
                    + Assign Selected Titles
                </button>
            </form>
        </div>
    @endif

    <!-- Books Filter Toolbar -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <form action="{{ route('admin.libraries.show', $library->id) }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin: 0;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search within {{ $library->name }}..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
            </div>

            <div style="width: 180px;">
                <select name="category_id" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                    <option value="">All Disciplines</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="width: 140px;">
                <select name="status" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                    <option value="">All Statuses</option>
                    <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <button type="submit" class="gold" style="padding: 8px 16px; font-size: 13px; cursor: pointer;">
                Filter
            </button>
            @if($search || $categoryId || $status)
                <a href="{{ route('admin.libraries.show', $library->id) }}" class="outline" style="padding: 8px 14px; font-size: 13px; text-decoration: none;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Publications Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                    <th style="padding: 12px 18px;">Title & Author</th>
                    <th style="padding: 12px 16px;">Discipline</th>
                    <th style="padding: 12px 16px;">Type</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px;">PDF File</th>
                    <th style="padding: 12px 18px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 700; color: var(--navy); font-size: 14px;">
                                <a href="{{ route('admin.books.edit', $book->id) }}" style="color: var(--navy); text-decoration: none;">
                                    {{ $book->title }}
                                </a>
                            </div>
                            <small style="color: #64748b;">By {{ $book->author_name }}</small>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="background: rgba(16, 41, 70, 0.05); color: var(--navy); padding: 3px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600;">
                                {{ $book->category?->name ?? 'General' }}
                            </span>
                        </td>
                        <td style="padding: 14px 16px; color: #475569;">
                            {{ $book->formattedPublicationType() }}
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; background: {{ $book->status === 'published' ? '#e6f9ed' : ($book->status === 'draft' ? '#fef3c7' : '#f1f5f9') }}; color: {{ $book->status === 'published' ? '#117838' : ($book->status === 'draft' ? '#92400e' : '#64748b') }};">
                                {{ $book->status }}
                            </span>
                        </td>
                        <td style="padding: 14px 16px;">
                            @if($book->primaryPdf)
                                <span style="color: #117838; font-weight: 600; font-size: 12px;">✓ {{ number_format($book->primaryPdf->file_size / 1024 / 1024, 1) }} MB</span>
                            @else
                                <span style="color: #64748b; font-size: 12px;">Synthetic fallback</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                <a href="{{ route('admin.books.edit', $book->id) }}" class="outline" style="padding: 4px 9px; font-size: 11.5px; text-decoration: none;">
                                    Edit
                                </a>
                                <form action="{{ route('admin.libraries.remove-book', [$library->id, $book->id]) }}" method="POST" onsubmit="return confirm('Remove publication from this library?');" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="background: none; border: 1px solid #cbd5e1; color: #64748b; border-radius: 4px; padding: 4px 8px; font-size: 11.5px; cursor: pointer;" title="Unassign from this library">
                                        Unassign
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px 20px; text-align: center; color: #64748b;">
                            No publications found in {{ $library->name }} matching your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $books->links() }}
    </div>
</div>
@endsection
