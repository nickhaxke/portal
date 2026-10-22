@extends('layouts.admin')

@section('title', 'Reports & Executive Analytics')
@section('header_title', 'Reports & Strategic Analytics')

@section('header_actions')
    <a href="{{ route('admin.reports.export-books') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
        <span>⬇</span> Export Inventory CSV
    </a>
@endsection

@section('content')
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px;">
    <div class="admin-card" style="margin: 0; border-top: 4px solid var(--navy);">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Curated Publications</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: var(--navy); margin: 6px 0 2px;">{{ $booksCount }}</div>
        <span style="font-size: 12px; color: var(--muted);">Available across all disciplines</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid var(--gold);">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Registered Readers</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: var(--gold); margin: 6px 0 2px;">{{ $readersCount }}</div>
        <span style="font-size: 12px; color: var(--muted);">Executive membership tier</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid #117838;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Total Reading Sessions</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: #117838; margin: 6px 0 2px;">{{ $readingSessions }}</div>
        <span style="font-size: 12px; color: var(--muted);">Real beacon progress events</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid #4a5d75;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Vault Storage Used</small>
        <div style="font: 700 34px 'Playfair Display', serif; color: #4a5d75; margin: 6px 0 2px;">{{ number_format($totalStorageBytes / 1048576, 2) }} MB</div>
        <span style="font-size: 12px; color: var(--muted);">Encrypted private book disk</span>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; margin-bottom: 24px;">
    <!-- Top Read Publications -->
    <div class="admin-card" style="margin: 0;">
        <h3 style="font: 700 17px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Most-Engaged Executive Publications</h3>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Publication</th>
                        <th>Type</th>
                        <th>Reading Sessions</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mostReadBooks as $book)
                        <tr>
                            <td>
                                <strong style="color: var(--navy); font-size: 13.5px;">{{ $book->title }}</strong>
                                <small style="display: block; color: var(--muted);">{{ $book->author_name }}</small>
                            </td>
                            <td>
                                <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 10.5px;">
                                    {{ ucfirst(str_replace('_', ' ', $book->publication_type ?? 'book')) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-success" style="font-size: 11px; font-weight: 700;">
                                    {{ $book->reading_progress_count }} Sessions
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('books.reader', $book->slug) }}" target="_blank" class="plain" style="font-size: 12.5px; color: var(--gold); font-weight: 600; text-decoration: none;">
                                    Read ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--muted); padding: 30px;">
                                No reading sessions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Publications by Type -->
    <div class="admin-card" style="margin: 0;">
        <h3 style="font: 700 17px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Publication Formats</h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($publicationsByType as $type => $count)
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                        <span>{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                    <div style="background: #eef1f5; height: 6px; border-radius: 3px; overflow: hidden;">
                        <div style="background: var(--gold); height: 100%; width: {{ $booksCount > 0 ? min(100, round(($count / $booksCount) * 100)) : 0 }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
