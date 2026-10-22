@extends('layouts.admin')

@section('title', 'Curated Collections Management')
@section('header_title', 'Curated Executive Collections')

@section('header_actions')
    <a href="{{ route('admin.collections.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        + Create Curated Collection
    </a>
@endsection

@section('content')
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0; color: var(--navy);">Curated Reading Lists & Anthologies</h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">Bespoke anthologies and thematic executive reading paths highlighted on the public portal.</p>
        </div>
        <span style="font-size: 13px; color: var(--muted);">
            Total Collections: <strong>{{ $collections->total() }}</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Collection Title</th>
                    <th>Slug</th>
                    <th>Publications Included</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Sort Order</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collections as $col)
                    <tr>
                        <td>
                            <strong style="color: var(--navy); font-size: 14.5px; display: block;">{{ $col->name }}</strong>
                            @if($col->description)
                                <small style="color: var(--muted); font-size: 12px; display: block; max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $col->description }}
                                </small>
                            @endif
                        </td>
                        <td style="color: #64748b; font-family: monospace; font-size: 12px;">
                            {{ $col->slug }}
                        </td>
                        <td>
                            <span class="badge badge-navy" style="font-size: 11.5px; font-weight: 700;">
                                {{ $col->books_count }} Publications
                            </span>
                        </td>
                        <td>
                            @if($col->is_featured)
                                <span style="color: var(--gold); display: inline-flex;" title="Featured"><x-icon name="star" size="15" /></span>
                            @else
                                <span style="color: #cbd5e1; display: inline-flex;"><x-icon name="star-outline" size="15" /></span>
                            @endif
                        </td>
                        <td>
                            @if($col->is_published)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-gold">Draft</span>
                            @endif
                        </td>
                        <td style="font-size: 13px; color: #64748b;">
                            {{ $col->sort_order }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('admin.collections.edit', $col->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); font-weight: 600; text-decoration: underline;">
                                    Edit
                                </a>
                                <form action="{{ route('admin.collections.destroy', $col->id) }}" method="POST" onsubmit="return confirm('Delete collection \'{{ addslashes($col->name) }}\'?')" style="margin:0; display:inline;">
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
                        <td colspan="7" style="text-align: center; color: var(--muted); padding: 40px;">
                            No curated collections created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $collections->links() }}
    </div>
</div>
@endsection
