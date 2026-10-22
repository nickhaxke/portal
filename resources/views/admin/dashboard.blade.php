@extends('layouts.admin')

@section('title', 'Overview Dashboard')
@section('header_title', 'Executive Intelligence Dashboard')

@section('content')
<!-- High-Level Metric Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 26px;">
    <div class="admin-card" style="margin: 0; border-top: 4px solid var(--navy);">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Curated Publications</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: var(--navy); margin: 6px 0 2px;">{{ $booksCount }}</div>
        <div style="font-size: 12px; color: var(--muted); display:flex; gap:8px;">
            <span><strong style="color:#117838;">{{ $statusCounts['published'] }}</strong> pub</span>
            <span>&bull;</span>
            <span><strong style="color:#b98227;">{{ $statusCounts['draft'] }}</strong> draft</span>
            <span>&bull;</span>
            <span><strong style="color:#64748b;">{{ $statusCounts['archived'] }}</strong> arch</span>
        </div>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid var(--gold);">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Disciplines & Taxonomy</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: var(--gold); margin: 6px 0 2px;">{{ $categoriesCount }}</div>
        <span style="font-size: 12px; color: var(--muted);">Curriculum and category nodes</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid #117838;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Executive Readers</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: #117838; margin: 6px 0 2px;">{{ $totalReaders }}</div>
        <span style="font-size: 12px; color: var(--muted);">
            <strong style="color:#117838;">{{ $verifiedReaders }}</strong> verified accounts
        </span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid #4a5d75;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Private Vault Storage</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: #4a5d75; margin: 6px 0 2px;">{{ $totalStorageFormatted }}</div>
        <span style="font-size: 12px; color: var(--muted);">{{ $pdfFilesCount }} PDFs &bull; {{ $epubFilesCount }} EPUBs</span>
    </div>
</div>

<!-- Publication Type Distribution Bar -->
<div class="admin-card" style="margin-bottom: 24px; padding: 18px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h4 style="margin: 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--navy); font-weight: 700;">
            Inventory by Publication Type
        </h4>
        <a href="{{ route('admin.books.create') }}" style="font-size: 12px; color: var(--gold); font-weight: 600; text-decoration: none;">+ Add Publication &rarr;</a>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px;">
        @foreach($booksByType as $typeKey => $typeData)
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">{{ $typeData['label'] }}</span>
                    <span style="color:var(--gold); display:inline-flex; align-items:center;"><x-icon :name="$typeData['icon']" size="18" /></span>
                </div>
                <div style="font: 700 22px 'DM Sans', sans-serif; color: var(--navy); margin-top: 6px;">{{ $typeData['count'] }}</div>
            </div>
        @endforeach
    </div>
</div>

<!-- Main 2-Column Section -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; margin-bottom: 24px;">
    <!-- Recent Publications Table -->
    <div class="admin-card" style="margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font: 700 17px 'Playfair Display', serif; margin: 0; color: var(--navy);">Recent Library Additions</h3>
            <a href="{{ route('admin.books.index') }}" style="font-size: 12px; color: var(--gold); font-weight: 600; text-decoration: none;">View All ({{ $booksCount }}) &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Publication</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBooks as $book)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 42px; border-radius: 3px; background: {{ $book->cover_bg ?? '#102946' }}; flex-shrink: 0; display: grid; place-items: center; color: white; font-size: 10px; font-weight: 700;">
                                        <x-icon name="book" size="14" />
                                    </div>
                                    <div>
                                        <a href="{{ route('books.show', $book->slug) }}" target="_blank" style="font-weight: 600; color: var(--navy); text-decoration: none; font-size: 13.5px; display:block; line-height: 1.3;">
                                            {{ Str::limit($book->title, 36) }}
                                        </a>
                                        <small style="color: var(--muted); font-size: 11.5px;">{{ $book->author_name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 10.5px;">
                                    {{ $book->formattedPublicationType() }}
                                </span>
                            </td>
                            <td>
                                @if($book->status === 'published')
                                    <span class="badge badge-success" style="font-size: 10.5px;">Published</span>
                                @elseif($book->status === 'draft')
                                    <span class="badge badge-gold" style="font-size: 10.5px;">Draft</span>
                                @else
                                    <span class="badge badge-navy" style="font-size: 10.5px;">Archived</span>
                                @endif
                            </td>
                            <td style="color: var(--muted); font-size: 12px;">
                                {{ $book->created_at->format('M d, Y') }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <a href="{{ route('admin.books.edit', $book->id) }}" style="font-size: 12px; color: var(--navy); text-decoration: underline;">
                                        Edit
                                    </a>
                                    <a href="{{ route('books.reader', $book->slug) }}" target="_blank" style="font-size: 12px; color: var(--gold); font-weight: 600; text-decoration: none;">
                                        Read ↗
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--muted); padding: 30px;">
                                No publications added yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Side: Reading Activity & Quick Launch -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Real Reading Activity -->
        <div class="admin-card" style="margin: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0; color: var(--navy);">Reader Engagement</h4>
                <span class="badge" style="background:#dbeafe; color:#1e40af; font-size:10px;">{{ $totalReadingSessions }} Sessions</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse($recentReadingActivity as $progress)
                    <div style="padding: 8px 10px; background: #f8fafc; border: 1px solid #eef2f6; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--navy);">
                            <span>{{ $progress->user?->name ?? 'Reader' }}</span>
                            <span style="color: var(--gold);">{{ $progress->percent_complete }}%</span>
                        </div>
                        <div style="font-size: 11px; color: var(--muted); margin: 2px 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $progress->book?->title ?? 'Publication' }}
                        </div>
                        <div style="background: #e2e8f0; height: 4px; border-radius: 2px; overflow: hidden;">
                            <div style="background: var(--gold); height: 100%; width: {{ $progress->percent_complete }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--muted); font-size: 12px; padding: 15px 0;">
                        No active reading sessions recorded yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Discipline Distribution -->
        <div class="admin-card" style="margin: 0;">
            <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 14px; color: var(--navy);">Disciplines Distribution</h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($categories as $cat)
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px; align-items: center;">
                            <span style="display:inline-flex; align-items:center; gap:6px;"><x-icon :name="$cat->icon_name" size="14" /> {{ $cat->name }}</span>
                            <strong>{{ $cat->books_count }}</strong>
                        </div>
                        <div style="background: #eef1f5; height: 5px; border-radius: 2px; overflow: hidden;">
                            <div style="background: var(--navy); height: 100%; width: {{ $booksCount > 0 ? min(100, round(($cat->books_count / $booksCount) * 100)) : 0 }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Bottom Grid: Recent Audit Activity & Calibre Import Batches -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <!-- Recent Audit Logs -->
    <div class="admin-card" style="margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0; color: var(--navy);">Administrative Activity Log</h4>
            <a href="{{ route('admin.audit-logs.index') }}" style="font-size: 12px; color: var(--gold); font-weight: 600; text-decoration: none;">View All &rarr;</a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentAuditLogs as $log)
                <div style="padding: 8px 12px; background: #ffffff; border: 1px solid #eef2f6; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span class="badge" style="background:#0f172a; color:#fff; font-size:10px; margin-right:6px;">{{ $log->action }}</span>
                        <span style="font-size: 12px; font-weight: 600; color: #1e293b;">{{ $log->user?->name ?? 'System' }}</span>
                        <small style="display:block; color:#64748b; font-size:11px;">IP: {{ $log->ip_address ?? '127.0.0.1' }}</small>
                    </div>
                    <small style="color: #94a3b8; font-size: 11px;">{{ $log->created_at->diffForHumans() }}</small>
                </div>
            @empty
                <div style="text-align: center; color: var(--muted); font-size: 12px; padding: 20px 0;">
                    No mutation audit records yet.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Import Status -->
    <div class="admin-card" style="margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0; color: var(--navy);">Calibre & Bulk Import Batches</h4>
            <a href="{{ route('admin.imports.index') }}" style="font-size: 12px; color: var(--gold); font-weight: 600; text-decoration: none;">Manage Imports &rarr;</a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentImports as $import)
                <div style="padding: 10px 14px; background: #ffffff; border: 1px solid #eef2f6; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="font-size: 12.5px; color: var(--navy); display:block;">{{ $import->batch_code }}</strong>
                        <small style="color: #64748b; font-size: 11.5px;">
                            {{ $import->imported_count }} imported &bull; {{ $import->duplicate_count }} dups &bull; {{ $import->failed_count }} failed
                        </small>
                    </div>
                    <div>
                        @if($import->status === 'completed')
                            <span class="badge badge-success" style="font-size: 10px;">Completed</span>
                        @elseif($import->status === 'processing')
                            <span class="badge badge-gold" style="font-size: 10px;">Processing</span>
                        @else
                            <span class="badge" style="background:#fee2e2; color:#991b1b; font-size:10px;">{{ ucfirst($import->status) }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--muted); font-size: 12px; padding: 20px 0;">
                    No migration batches executed yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
