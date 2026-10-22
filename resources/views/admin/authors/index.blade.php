@extends('layouts.admin')

@section('title', 'Authors & Publishers Management')
@section('header_title', 'Authors & Contributors Directory')

@section('header_actions')
    <a href="{{ route('admin.authors.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        + Add Author
    </a>
@endsection

@section('content')
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="{{ route('admin.authors.index') }}" style="display: flex; gap: 10px; flex: 1; max-width: 400px;">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Search authors..." 
                class="field" 
                style="flex: 1; font-size: 13px;"
            >
            <button type="submit" class="outline" style="padding: 8px 16px;">Search</button>
            @if($search)
                <a href="{{ route('admin.authors.index') }}" class="plain" style="align-self: center; font-size: 12px; color: var(--muted);">Reset</a>
            @endif
        </form>

        <span style="font-size: 13px; color: var(--muted);">
            Total Contributors: <strong>{{ $authors->total() }}</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Contributor Name</th>
                    <th>Slug</th>
                    <th>Publications Attributed</th>
                    <th>Biographical Abstract</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($authors as $author)
                    <tr>
                        <td>
                            <strong style="color: var(--navy); font-size: 14px;">{{ $author->name }}</strong>
                        </td>
                        <td style="color: #64748b; font-family: monospace; font-size: 12px;">
                            {{ $author->slug }}
                        </td>
                        <td>
                            <span class="badge badge-navy" style="font-size: 11px; font-weight: 700;">
                                {{ $author->books_count }} Publications
                            </span>
                        </td>
                        <td style="color: var(--muted); font-size: 12px; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $author->bio ?? 'No biographical abstract provided.' }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 10px;">
                                <a href="{{ route('admin.authors.edit', $author->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); font-weight: 600; text-decoration: underline;">
                                    Edit & Merge
                                </a>
                                <form action="{{ route('admin.authors.destroy', $author->id) }}" method="POST" onsubmit="return confirm('Delete author \'{{ addslashes($author->name) }}\'?')" style="margin:0; display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:none; border:0; color:#991b1b; font-size:12.5px; cursor:pointer; padding:0;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--muted); padding: 40px;">
                            No authors found matching this query.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $authors->links() }}
    </div>
</div>
@endsection
