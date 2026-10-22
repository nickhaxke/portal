@extends('layouts.admin')

@section('title', 'Executive Insights CMS')
@section('header_title', 'Executive Insights & Editorial CMS')

@section('header_actions')
    <a href="{{ route('admin.insights.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        + New Executive Insight
    </a>
@endsection

@section('content')
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="{{ route('admin.insights.index') }}" style="display: flex; gap: 10px; flex: 1; max-width: 400px;">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Search insights..." 
                class="field" 
                style="flex: 1; font-size: 13px;"
            >
            <button type="submit" class="outline" style="padding: 8px 16px;">Search</button>
            @if($search)
                <a href="{{ route('admin.insights.index') }}" class="plain" style="align-self: center; font-size: 12px; color: var(--muted);">Reset</a>
            @endif
        </form>

        <span style="font-size: 13px; color: var(--muted);">
            Total Briefings: <strong>{{ $insights->total() }}</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 96px;">Artwork</th>
                    <th>Title & Briefing</th>
                    <th>Editorial Theme</th>
                    <th>Reading Length</th>
                    <th>Publishing Status</th>
                    <th>Date Published</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($insights as $insight)
                    <tr>
                        <td>
                            @if($insight->featuredImageUrl())
                                <img src="{{ $insight->featuredImageUrl() }}" alt="{{ $insight->image_alt ?: $insight->title }}" loading="lazy" style="width: 84px; height: 52px; object-fit: cover; border-radius: 5px; border: 1px solid #e2e8f0; display: block;">
                            @else
                                <div style="width: 84px; height: 52px; border-radius: 5px; background: #102946; color: var(--gold); display: flex; align-items: center; justify-content: center;">
                                    <x-icon :name="$insight->icon_name" size="20" />
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong style="color: var(--navy); font-size: 14px; display: block;">
                                {{ $insight->title }}
                                @if($insight->is_featured)
                                    <span class="badge badge-gold" style="font-size: 10px; margin-left: 4px; vertical-align: middle;">Featured</span>
                                @endif
                            </strong>
                            <small style="color: #64748b; font-size: 11.5px;">{{ $insight->author_name ?: 'Executive Editorial Board' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-navy" style="font-size: 11px;">
                                {{ $insight->category_name }}
                            </span>
                        </td>
                        <td style="color: var(--muted); font-size: 12.5px;">
                            {{ $insight->read_time }}
                        </td>
                        <td>
                            @if($insight->published_at)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-gold">Draft</span>
                            @endif
                        </td>
                        <td style="color: var(--muted); font-size: 12px;">
                            {{ $insight->published_at ? $insight->published_at->format('M d, Y') : 'Not Published' }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('insights.show', $insight->slug) }}" target="_blank" class="plain" style="font-size: 12.5px; color: var(--gold); font-weight: 600; text-decoration: none;">
                                    Preview ↗
                                </a>
                                <a href="{{ route('admin.insights.edit', $insight->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); text-decoration: underline;">
                                    Edit
                                </a>
                                <form action="{{ route('admin.insights.destroy', $insight->id) }}" method="POST" onsubmit="return confirm('Delete insight \'{{ addslashes($insight->title) }}\'?')" style="margin:0; display:inline;">
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
                            No executive insights published yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $insights->links() }}
    </div>
</div>
@endsection
