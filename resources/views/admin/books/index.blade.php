@extends('layouts.admin')

@section('title', 'Publication Catalogue Management')
@section('header_title', 'Curated Publication Catalogue')

@section('header_actions')
    <a href="{{ route('admin.books.create') }}" class="primary" style="padding: 8px 16px; font-size: 13px; text-decoration:none; margin-right: 8px;">
        + Add New Publication
    </a>
    <a href="{{ route('explore') }}" target="_blank" class="outline" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        Public Catalogue ↗
    </a>
@endsection

@section('content')
<!-- Lifecycle Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; overflow-x: auto;">
    <a href="{{ route('admin.books.index') }}" class="plain" style="padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration:none; {{ empty($status) ? 'background: var(--navy); color: white;' : 'background: #f1f5f9; color: #475569;' }}">
        All Publications ({{ $counts['all'] }})
    </a>
    <a href="{{ route('admin.books.index', ['status' => 'published']) }}" class="plain" style="padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration:none; {{ $status === 'published' ? 'background: #117838; color: white;' : 'background: #f1f5f9; color: #475569;' }}">
        Published ({{ $counts['published'] }})
    </a>
    <a href="{{ route('admin.books.index', ['status' => 'draft']) }}" class="plain" style="padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration:none; {{ $status === 'draft' ? 'background: var(--gold); color: #102946;' : 'background: #f1f5f9; color: #475569;' }}">
        Draft ({{ $counts['draft'] }})
    </a>
    <a href="{{ route('admin.books.index', ['status' => 'archived']) }}" class="plain" style="padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration:none; {{ $status === 'archived' ? 'background: #475569; color: white;' : 'background: #f1f5f9; color: #475569;' }}">
        Archived ({{ $counts['archived'] }})
    </a>
    <a href="{{ route('admin.books.index', ['status' => 'trashed']) }}" class="plain" style="padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration:none; {{ $status === 'trashed' ? 'background: #991b1b; color: white;' : 'background: #fee2e2; color: #991b1b;' }}">
        Trash / Soft-Deleted ({{ $counts['trashed'] }})
    </a>
</div>

<div class="admin-card">
    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.books.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
        @if($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif

        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search by title, author, DOI, ISBN or discipline..." 
            class="field" 
            style="flex: 1; min-width: 240px;"
        >

        <select name="category_id" class="field" style="width: 190px;" onchange="this.form.submit()">
            <option value="">All Disciplines</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }} style="font-weight: 700;">
                    {{ $cat->name }}
                </option>
                @if($cat->children)
                    @foreach($cat->children as $child)
                        <option value="{{ $child->id }}" {{ $categoryId == $child->id ? 'selected' : '' }}>
                            &nbsp;&nbsp;&nbsp;&nbsp;↳ {{ $child->name }}
                        </option>
                    @endforeach
                @endif
            @endforeach
        </select>

        <select name="publication_type" class="field" style="width: 170px;" onchange="this.form.submit()">
            <option value="">All Types</option>
            @foreach($publicationTypes as $typeKey => $typeData)
                <option value="{{ $typeKey }}" {{ ($publicationType ?? '') == $typeKey ? 'selected' : '' }}>
                    {{ $typeData['badge'] }}
                </option>
            @endforeach
        </select>

        <select name="sort_by" class="field" style="width: 160px;" onchange="this.form.submit()">
            <option value="latest" {{ ($sortBy ?? '') === 'latest' ? 'selected' : '' }}>Newest First</option>
            <option value="title_asc" {{ ($sortBy ?? '') === 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
            <option value="title_desc" {{ ($sortBy ?? '') === 'title_desc' ? 'selected' : '' }}>Title (Z-A)</option>
            <option value="year_desc" {{ ($sortBy ?? '') === 'year_desc' ? 'selected' : '' }}>Publication Year</option>
            <option value="pages_desc" {{ ($sortBy ?? '') === 'pages_desc' ? 'selected' : '' }}>Page Count</option>
        </select>

        <button type="submit" class="outline" style="padding: 9px 18px;">Filter</button>
        @if($search || $categoryId || !empty($publicationType) || (!empty($status) && $status !== 'all') || (!empty($sortBy) && $sortBy !== 'latest'))
            <a href="{{ route('admin.books.index') }}" class="plain" style="align-self: center; font-size: 13px; color: var(--muted); text-decoration: underline;">Reset</a>
        @endif
    </form>

    <!-- Bulk Action Form -->
    <form id="bulkForm" action="{{ route('admin.books.bulk') }}" method="POST" style="margin-bottom: 16px;">
        @csrf
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--navy); margin: 0;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="cursor: pointer;">
                    Select All
                </label>
                <span id="selectedCount" style="font-size: 12px; color: #64748b;">(0 selected)</span>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <select name="action" class="field" style="padding: 6px 12px; font-size: 13px; width: 170px;" required>
                    <option value="">Bulk Action...</option>
                    @if($status === 'trashed')
                        <option value="restore">Restore Selected</option>
                    @else
                        <option value="publish">Publish Selected</option>
                        <option value="draft">Move to Draft</option>
                        <option value="archive">Archive Selected</option>
                        <option value="soft_delete">Move to Trash</option>
                    @endif
                </select>
                <button type="submit" class="gold" style="padding: 7px 14px; font-size: 12.5px; border:0; cursor: pointer;" onclick="return confirmBulkAction()">
                    Apply to Selected
                </button>
            </div>
        </div>

        <!-- Books Table -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th style="width: 50px;">Cover</th>
                        <th>Title & Contributor</th>
                        <th>Type & Discipline</th>
                        <th>Year / Pages</th>
                        <th>Lifecycle & Access</th>
                        <th>Vault File</th>
                        <th>Featured</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                        <tr>
                            <td>
                                <input type="checkbox" name="book_ids[]" value="{{ $book->id }}" class="book-checkbox" onchange="updateSelectedCount()" style="cursor: pointer;">
                            </td>
                            <td>
                                @if($book->cover_url)
                                    <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" style="width: 38px; height: 52px; object-fit: cover; border-radius: 3px; box-shadow: 0 1px 4px rgba(0,0,0,0.15);">
                                @else
                                    <div style="width: 38px; height: 52px; border-radius: 3px; background: {{ $book->cover_bg ?? '#102946' }}; color: {{ $book->cover_fg ?? '#ffffff' }}; display: flex; flex-direction: column; justify-content: space-between; align-items:center; padding: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.15); font-size: 7px; text-align: center; overflow: hidden;">
                                        <x-icon name="book" size="12" />
                                        <b style="font-size: 7.5px; line-height: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $book->title }}</b>
                                        <span></span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong style="color: var(--navy); font-size: 14px; display: block; line-height: 1.3;">
                                    {{ $book->title }}
                                </strong>
                                @if($book->subtitle)
                                    <small style="color: var(--muted); display: block; font-style: italic; font-size: 11.5px;">{{ $book->subtitle }}</small>
                                @endif
                                <small style="color: var(--muted); font-size: 11.5px;">By {{ $book->author_name }}</small>
                                @if($book->doi)
                                    <small style="display: block; color: var(--gold); font-size: 11px;">DOI: {{ $book->doi }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge" style="background: #eef2f6; color: var(--navy); font-size: 10.5px; font-weight: 700; margin-bottom: 3px; display: inline-block;">
                                    {{ $book->formattedPublicationType() }}
                                </span>
                                <span class="badge badge-navy" style="display: block; max-width: fit-content; font-size: 10.5px;">
                                    {{ $book->category?->name ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td style="color: var(--muted); font-size: 12.5px;">
                                {{ $book->publication_year ?? $book->published_year ?? '—' }} &middot; {{ $book->pages_count ? $book->pages_count . ' pgs' : '—' }}
                            </td>
                            <td>
                                @if($book->trashed())
                                    <span class="badge" style="background:#fee2e2; color:#991b1b; font-size: 10.5px;">In Trash</span>
                                @elseif($book->status === 'published')
                                    <span class="badge badge-success" style="font-size: 10.5px;">Published</span>
                                @elseif($book->status === 'draft')
                                    <span class="badge badge-gold" style="font-size: 10.5px;">Draft</span>
                                @else
                                    <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 10.5px;">Archived</span>
                                @endif

                                @if($book->allow_download)
                                    <small style="display: inline-flex; align-items: center; gap: 4px; color: #117838; font-size: 10.5px; margin-top: 3px;"><x-icon name="download" size="11" /> Downloadable</small>
                                @else
                                    <small style="display: inline-flex; align-items: center; gap: 4px; color: var(--muted); font-size: 10.5px; margin-top: 3px;"><x-icon name="lock" size="11" /> Online Only</small>
                                @endif
                            </td>
                            <td>
                                @if($book->primaryPdf)
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span class="badge badge-success" style="font-size: 10px; display: inline-flex; align-items: center; gap: 3px;"><x-icon name="check" size="11" /> {{ $book->primaryPdf->formatted_size }}</span>
                                        <a href="{{ route('books.reader', $book->slug) }}" target="_blank" style="color: var(--gold); font-size: 11.5px; font-weight: 700; text-decoration: none;" title="Open Online Reader">
                                            Read ↗
                                        </a>
                                    </div>
                                @else
                                    <span class="badge badge-gold" style="font-size: 10px;">No File</span>
                                @endif
                            </td>
                            <td>
                                @if($book->is_featured)
                                    <span style="color: var(--gold); display: inline-flex;" title="Featured"><x-icon name="star" size="15" /></span>
                                @else
                                    <span style="color: #cbd5e1; display: inline-flex;"><x-icon name="star-outline" size="15" /></span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    @if($book->trashed())
                                        <button type="submit" form="restore-{{ $book->id }}" style="background:none; border:0; color:#117838; font-size:12.5px; font-weight:600; cursor:pointer; padding:0;">
                                            Restore
                                        </button>
                                        <button type="submit" form="force-{{ $book->id }}" style="background:none; border:0; color:#991b1b; font-size:12.5px; cursor:pointer; padding:0;">
                                            Permanent Delete
                                        </button>
                                    @else
                                        <a href="{{ route('books.show', $book->slug) }}" target="_blank" class="plain" style="font-size: 12.5px; color: var(--navy); text-decoration:none;" title="View public page">
                                            View
                                        </a>
                                        <a href="{{ route('admin.books.edit', $book->id) }}" class="plain" style="font-size: 12.5px; color: var(--gold); font-weight: 600; text-decoration:none;" title="Edit book">
                                            Edit
                                        </a>
                                        <button type="submit" form="delete-{{ $book->id }}" style="background: none; border: 0; color: #991b1b; font-size: 12.5px; cursor: pointer; padding: 0;">
                                            Trash
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--muted); padding: 50px;">
                                No publications found in this view.
                                <div style="margin-top: 15px;">
                                    <a href="{{ route('admin.books.create') }}" class="gold" style="text-decoration:none; padding:8px 16px;">+ Add New Publication</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Hidden Individual Action Forms -->
    @foreach($books as $book)
        @if($book->trashed())
            <form id="restore-{{ $book->id }}" action="{{ route('admin.books.restore', $book->id) }}" method="POST" style="display:none;">
                @csrf
            </form>
            <form id="force-{{ $book->id }}" action="{{ route('admin.books.force-delete', $book->id) }}" method="POST" onsubmit="return confirm('PERMANENT DELETION: Are you sure? All private files and database records for \'{{ addslashes($book->title) }}\' will be completely destroyed. This cannot be undone!')" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        @else
            <form id="delete-{{ $book->id }}" action="{{ route('admin.books.destroy', $book->id) }}" method="POST" onsubmit="return confirm('Move \'{{ addslashes($book->title) }}\' to trash? You can restore it anytime.')" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

    <div style="margin-top: 24px;">
        {{ $books->links() }}
    </div>
</div>

<script>
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.book-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.book-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = '(' + checked + ' selected)';
    }

    function confirmBulkAction() {
        const checked = document.querySelectorAll('.book-checkbox:checked').length;
        if (checked === 0) {
            alert('Please select at least one publication.');
            return false;
        }
        return confirm('Apply bulk action to ' + checked + ' publication(s)?');
    }
</script>
@endsection
