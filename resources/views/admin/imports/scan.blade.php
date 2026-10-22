@extends('layouts.admin')

@section('title', 'Calibre Library Scan Results')
@section('header_title', 'Pre-Import Scan & Validation')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.imports.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Migration Dashboard</a>
</div>

@if(!$scanResult['valid'])
    <div class="admin-card" style="border-left: 4px solid #991b1b; background: #fff5f5;">
        <h3 style="color: #991b1b; font-size: 18px; margin: 0 0 10px;">Library Validation Failed</h3>
        <p style="color: #4b5563; font-size: 14px; margin: 0;">{{ $scanResult['error'] }}</p>
        <div style="margin-top: 18px;">
            <a href="{{ route('admin.imports.index') }}" class="outline" style="padding: 8px 16px;">Try Another Directory</a>
        </div>
    </div>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="admin-card" style="margin: 0;">
            <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Publications in Calibre</small>
            <div style="font: 700 32px 'Playfair Display', serif; color: var(--navy); margin: 6px 0 2px;">{{ $scanResult['books_count'] }}</div>
            <span style="font-size: 12px; color: var(--muted);">Recorded in metadata.db</span>
        </div>

        <div class="admin-card" style="margin: 0;">
            <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Authors Detected</small>
            <div style="font: 700 32px 'Playfair Display', serif; color: var(--gold); margin: 6px 0 2px;">{{ $scanResult['authors_count'] }}</div>
            <span style="font-size: 12px; color: var(--muted);">Unique contributor entities</span>
        </div>

        <div class="admin-card" style="margin: 0;">
            <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Potential Duplicates</small>
            <div style="font: 700 32px 'Playfair Display', serif; color: #d97706; margin: 6px 0 2px;">{{ $scanResult['potential_duplicates_in_sample'] }}</div>
            <span style="font-size: 12px; color: var(--muted);">Found in preview sample</span>
        </div>

        <div class="admin-card" style="margin: 0;">
            <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Missing Files on Disk</small>
            <div style="font: 700 32px 'Playfair Display', serif; color: {{ $scanResult['missing_files_in_sample'] > 0 ? '#991b1b' : '#117838' }}; margin: 6px 0 2px;">
                {{ $scanResult['missing_files_in_sample'] }}
            </div>
            <span style="font-size: 12px; color: var(--muted);">Missing directory entries</span>
        </div>
    </div>

    <!-- Formats Breakdown Card -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 12px; color: var(--navy);">Format Distribution in Source</h4>
        <div style="display: flex; gap: 14px; flex-wrap: wrap;">
            @foreach($scanResult['formats'] as $fmt)
                <div style="padding: 8px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <strong style="color: var(--navy); font-size: 14px; text-transform: uppercase;">{{ $fmt['format'] }}</strong>
                    <span style="color: var(--muted); font-size: 12px; margin-left: 6px;">({{ $fmt['count'] }} files)</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Confirm Execution Card -->
    <div class="admin-card" style="background: linear-gradient(135deg, #f8fafc, #edf2f7); border-left: 4px solid var(--gold); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h4 style="margin: 0 0 6px; font-size: 16px; color: var(--navy);">Source Ready for Migration</h4>
                <p style="margin: 0; font-size: 13px; color: var(--muted);">
                    Source: <code>{{ $path }}</code> &bull; All prerequisites met.
                </p>
            </div>
            <form action="{{ route('admin.imports.store') }}" method="POST" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 0;">
                @csrf
                <input type="hidden" name="library_path" value="{{ $path }}">
                @if(isset($libraries) && $libraries->isNotEmpty())
                    <select name="library_id" class="field" style="font-size: 13px; padding: 10px 14px;">
                        @foreach($libraries as $lib)
                            <option value="{{ $lib->id }}">{{ $lib->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="duplicate_mode" class="field" style="font-size: 13px; padding: 10px 14px;">
                    <option value="skip">Skip duplicates</option>
                    <option value="update">Update existing</option>
                    <option value="import_new">Import as new</option>
                </select>
                <button type="submit" class="gold" style="padding: 12px 24px; font-size: 14px;" onclick="return confirm('Start migration batch now?')">
                    Confirm & Execute Migration &rarr;
                </button>
            </form>
        </div>
    </div>

    <!-- Sample Inspection Table -->
    <div class="admin-card">
        <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 14px; color: var(--navy);">Preview Sample (First 50 Entries)</h4>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Calibre ID</th>
                        <th>Title</th>
                        <th>Author(s)</th>
                        <th>Cover Available</th>
                        <th>Disk Files</th>
                        <th>Portal Duplicate?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scanResult['sample_books'] as $sample)
                        <tr>
                            <td style="font-weight: 700; color: #64748b;">#{{ $sample['id'] }}</td>
                            <td><strong style="color: var(--navy);">{{ $sample['title'] }}</strong></td>
                            <td style="color: var(--muted);">{{ $sample['author'] }}</td>
                            <td>
                                @if($sample['has_cover'])
                                    <span class="badge badge-success">Cover Found</span>
                                @else
                                    <span class="badge" style="background:#f1f5f9; color:#64748b;">No Cover</span>
                                @endif
                            </td>
                            <td>
                                @if($sample['files_found_on_disk'])
                                    <span style="color: #117838; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="check" size="12" /> Directory Exists</span>
                                @else
                                    <span style="color: #991b1b; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="alert" size="12" /> Missing on Disk</span>
                                @endif
                            </td>
                            <td>
                                @if($sample['exists_in_portal'])
                                    <span class="badge badge-gold">Duplicate (Will Skip)</span>
                                @else
                                    <span class="badge badge-success">New Publication</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
