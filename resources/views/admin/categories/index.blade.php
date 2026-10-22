@extends('layouts.admin')

@section('title', 'Disciplines & Categories')
@section('header_title', 'Curriculum Disciplines & Taxonomy')

@section('header_actions')
    <a href="{{ route('admin.categories.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        + Add Discipline / Subcategory
    </a>
@endsection

@section('content')
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0; color: var(--navy);">Executive Knowledge Disciplines</h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">Hierarchical organization of curriculum areas, parent disciplines, and specialized subcategories.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Icon</th>
                    <th>Discipline / Category Name</th>
                    <th>Slug</th>
                    <th>Publications</th>
                    <th>Sort Order</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disciplines as $discipline)
                    <tr style="background: #fafaf9;">
                        <td style="text-align: center; color: var(--gold);"><x-icon :name="$discipline->icon_name" size="20" /></td>
                        <td>
                            <strong style="color: var(--navy); font-size: 15px;">{{ $discipline->name }}</strong>
                            <small style="color: var(--gold); display: block; font-weight: 600; font-size: 11px; text-transform: uppercase;">Parent Discipline</small>
                            @if($discipline->description)
                                <small style="color: var(--muted); display: block; font-size: 12px;">{{ $discipline->description }}</small>
                            @endif
                        </td>
                        <td style="color: #64748b; font-family: monospace; font-size: 12px;">{{ $discipline->slug }}</td>
                        <td>
                            <span class="badge badge-navy" style="font-size: 12px; font-weight: 700;">
                                {{ $discipline->books_count }} Publications
                            </span>
                        </td>
                        <td style="font-size: 13px; color: #64748b;">{{ $discipline->sort_order }}</td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('admin.categories.edit', $discipline->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); font-weight: 600; text-decoration: underline;">
                                    Edit
                                </a>
                                <form action="{{ route('admin.categories.destroy', $discipline->id) }}" method="POST" onsubmit="return confirm('Delete discipline \'{{ addslashes($discipline->name) }}\'?')" style="margin:0; display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:none; border:0; color:#991b1b; font-size:12.5px; cursor:pointer; padding:0;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Nested Subcategories -->
                    @foreach($discipline->children as $child)
                        <tr>
                            <td style="text-align: center; color: #94a3b8; font-size: 14px;">↳</td>
                            <td style="padding-left: 32px;">
                                <strong style="color: #334155; font-size: 13.5px; display:inline-flex; align-items:center; gap:6px;"><x-icon :name="$child->icon_name" size="14" /> {{ $child->name }}</strong>
                                <small style="color: #94a3b8; display: block; font-size: 11.5px;">Subcategory under {{ $discipline->name }}</small>
                            </td>
                            <td style="color: #94a3b8; font-family: monospace; font-size: 11.5px;">{{ $child->slug }}</td>
                            <td>
                                <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 11px;">
                                    {{ $child->books_count }} Publications
                                </span>
                            </td>
                            <td style="font-size: 12px; color: #94a3b8;">{{ $child->sort_order }}</td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    <a href="{{ route('admin.categories.edit', $child->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); text-decoration: underline;">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $child->id) }}" method="POST" onsubmit="return confirm('Delete subcategory \'{{ addslashes($child->name) }}\'?')" style="margin:0; display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none; border:0; color:#991b1b; font-size:12.5px; cursor:pointer; padding:0;">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted); padding: 40px;">
                            No disciplines or categories configured yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
