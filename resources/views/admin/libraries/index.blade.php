@extends('layouts.admin')

@section('title', 'Library Management')
@section('header_title', 'Libraries')

@section('header_actions')
    <a href="{{ route('admin.libraries.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
        <span>+</span> Create Library
    </a>
@endsection

@section('content')
<div style="margin-bottom: 25px;">
    <!-- Metric Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Libraries</span>
                <span style="background: rgba(16, 41, 70, 0.08); color: var(--navy); border-radius: 6px; padding: 6px 10px; display:inline-flex;"><x-icon name="library" size="18" /></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--navy); font-family: 'Playfair Display', serif;">
                {{ $stats['total_libraries'] }}
            </div>
            <small style="color: #64748b; font-size: 12px;">{{ $stats['published_libraries'] }} published & active</small>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Books</span>
                <span style="background: rgba(185, 130, 39, 0.12); color: var(--gold); border-radius: 6px; padding: 6px 10px; display:inline-flex;"><x-icon name="publications" size="18" /></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--navy); font-family: 'Playfair Display', serif;">
                {{ $stats['total_books_assigned'] }}
            </div>
            <small style="color: #64748b; font-size: 12px;">Across all Calibre-style collections</small>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Unassigned Titles</span>
                <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 6px; padding: 6px 10px; display:inline-flex;"><x-icon name="alert" size="18" /></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: {{ $stats['unassigned_books'] > 0 ? '#ef4444' : 'var(--navy)' }}; font-family: 'Playfair Display', serif;">
                {{ $stats['unassigned_books'] }}
            </div>
            <small style="color: #64748b; font-size: 12px;">{{ $stats['unassigned_books'] > 0 ? 'Action required: assign to library' : 'All titles organized' }}</small>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <form action="{{ route('admin.libraries.index') }}" method="GET" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center; margin: 0;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search libraries by name or description..." style="width: 100%; padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;">
            </div>

            <div style="width: 180px;">
                <select name="visibility" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; background: white;">
                    <option value="">All Visibility</option>
                    <option value="public" {{ $visibility === 'public' ? 'selected' : '' }}>Public</option>
                    <option value="restricted" {{ $visibility === 'restricted' ? 'selected' : '' }}>Restricted</option>
                    <option value="private" {{ $visibility === 'private' ? 'selected' : '' }}>Private</option>
                </select>
            </div>

            <button type="submit" class="gold" style="padding: 9px 18px; font-size: 13.5px; cursor: pointer;">
                Filter
            </button>
            @if($search || $visibility)
                <a href="{{ route('admin.libraries.index') }}" class="outline" style="padding: 9px 16px; font-size: 13.5px; text-decoration: none;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Libraries Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        @forelse($libraries as $library)
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.03); display: flex; flex-direction: column;">
                <div style="padding: 20px; flex: 1;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 41, 70, 0.07); color: var(--navy); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-icon :name="$library->icon_name" size="20" />
                            </span>
                            <div>
                                <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: var(--navy);">
                                    <a href="{{ route('admin.libraries.show', $library->id) }}" style="color: var(--navy); text-decoration: none;">
                                        {{ $library->name }}
                                    </a>
                                </h3>
                                <small style="color: #64748b; font-size: 11px; font-family: monospace;">{{ $library->slug }}</small>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                            @if($library->is_published)
                                <span style="background: #e6f9ed; color: #117838; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px;">Published</span>
                            @else
                                <span style="background: #f1f5f9; color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px;">Draft</span>
                            @endif

                            <span style="font-size: 11px; text-transform: capitalize; color: #64748b;">
                                {{ $library->visibility }}
                            </span>
                        </div>
                    </div>

                    <p style="color: #475569; font-size: 13px; line-height: 1.5; margin: 10px 0 16px; min-height: 40px;">
                        {{ Str::limit($library->description ?: 'No detailed description provided.', 120) }}
                    </p>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #64748b;">
                        <div>
                            <strong style="color: var(--navy); font-size: 15px;">{{ $library->books_count }}</strong>
                            <span>total {{ Str::plural('book', $library->books_count) }}</span>
                        </div>
                        <div>
                            <strong style="color: var(--gold); font-size: 14px;">{{ $library->published_books_count }}</strong>
                            <span>published</span>
                        </div>
                    </div>
                </div>

                <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <a href="{{ route('admin.libraries.show', $library->id) }}" class="plain" style="font-size: 12.5px; font-weight: 600; color: var(--navy);">
                        Manage Books &rarr;
                    </a>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="{{ route('libraries.show', $library->slug) }}" target="_blank" class="outline" style="padding: 4px 9px; font-size: 11.5px; text-decoration: none;" title="View public library catalogue">
                            View ↗
                        </a>
                        <a href="{{ route('admin.libraries.edit', $library->id) }}" class="outline" style="padding: 4px 9px; font-size: 11.5px; text-decoration: none;">
                            Edit
                        </a>
                        <form action="{{ route('admin.libraries.destroy', $library->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this library? Associated books will be moved to a fallback library.');" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: none; border: 1px solid #fecaca; color: #ef4444; border-radius: 4px; padding: 4px 8px; font-size: 11.5px; cursor: pointer;">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 45px 20px; text-align: center;">
                <x-icon name="library" size="42" />
                <h3 style="margin: 14px 0 6px; color: var(--navy);">No libraries found</h3>
                <p style="color: #64748b; font-size: 13.5px; max-width: 400px; margin: 0 auto 18px;">
                    {{ $search ? "No libraries matched '{$search}'." : 'Create separate digital libraries for executive leadership, research, and governance.' }}
                </p>
                <a href="{{ route('admin.libraries.create') }}" class="gold" style="padding: 9px 20px; font-size: 13px; text-decoration: none; display: inline-block;">
                    Create First Library
                </a>
            </div>
        @endforelse
    </div>

    <div style="margin-top: 25px;">
        {{ $libraries->links() }}
    </div>
</div>
@endsection
