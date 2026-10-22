@extends('layouts.admin')

@section('title', 'Bulk Import & Calibre Migration')
@section('header_title', 'Calibre Library Migration Engine')

@section('content')
<!-- High Level Import Metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px;">
    <div class="admin-card" style="margin: 0; border-top: 4px solid var(--navy);">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Import Batches</small>
        <div style="font: 700 32px 'Playfair Display', serif; color: var(--navy); margin: 6px 0 2px;">{{ $batches->total() }}</div>
        <span style="font-size: 12px; color: var(--muted);">Total migration executions</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid #117838;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Migrated Publications</small>
        <div style="font: 700 32px 'Playfair Display', serif; color: #117838; margin: 6px 0 2px;">{{ $totalImported }}</div>
        <span style="font-size: 12px; color: var(--muted);">Integrated into private vault</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid var(--gold);">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Duplicate Detections</small>
        <div style="font: 700 32px 'Playfair Display', serif; color: var(--gold); margin: 6px 0 2px;">{{ $totalDuplicates }}</div>
        <span style="font-size: 12px; color: var(--muted);">SHA-256 hash & ID deduplicated</span>
    </div>

    <div class="admin-card" style="margin: 0; border-top: 4px solid #4a5d75;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Engine Status</small>
        <div style="font: 700 24px 'Playfair Display', serif; color: #4a5d75; margin: 10px 0 4px;">
            @if($activeBatch)
                <span style="color: #b98227;">Processing Batch...</span>
            @else
                <span style="color: #117838;">Engine Ready</span>
            @endif
        </div>
        <span style="font-size: 12px; color: var(--muted);">SQLite PDO & Vault Pipeline Active</span>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; margin-bottom: 24px;">
    <!-- Step 1: Scan Calibre Library -->
    <div class="admin-card" style="margin: 0;">
        <div class="eyebrow" style="color: var(--gold); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 6px;">Step 1 — Pre-Import Validation</div>
        <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 12px; color: var(--navy);">Scan Calibre Source Directory</h3>
        <p style="color: var(--muted); font-size: 13px; line-height: 1.5; margin-bottom: 18px;">
            Inspects the local or server-side Calibre repository. Reads <code>metadata.db</code> in read-only mode, extracts schema statistics, verifies relative PDF/EPUB binary paths, and detects duplicate records before importing.
        </p>

        <form action="{{ route('admin.imports.scan') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label for="library_path" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Calibre Library Root Directory Path <span style="color:#c53030;">*</span>
                </label>
                <input 
                    type="text" 
                    id="library_path" 
                    name="library_path" 
                    class="field" 
                    value="{{ old('library_path', 'c:\wamp64\www\portal\tests\Fixtures\calibre') }}" 
                    placeholder="e.g. C:\Calibre Library or /var/data/calibre" 
                    required 
                    style="width: 100%; font-size: 13px;"
                >
                <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 4px;">
                    Must contain the <code>metadata.db</code> file and author subfolders.
                </small>
            </div>

            <button type="submit" class="outline" style="padding: 10px 18px; font-size: 13px;">
                Scan & Preview Library &rarr;
            </button>
        </form>
    </div>

    <!-- Step 2: Direct Batch Execution -->
    <div class="admin-card" style="margin: 0;">
        <div class="eyebrow" style="color: var(--gold); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 6px;">Step 2 — Migration Execution</div>
        <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 12px; color: var(--navy);">Execute Controlled Import</h3>
        <p style="color: var(--muted); font-size: 13px; line-height: 1.5; margin-bottom: 18px;">
            Executes migration into Portal models. Copies documents to the encrypted private storage vault, preserves Calibre IDs, extracts tags, series, and calculates SHA-256 integrity hashes.
        </p>

        <form action="{{ route('admin.imports.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label for="exec_library_path" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Confirmed Library Path <span style="color:#c53030;">*</span>
                </label>
                <input 
                    type="text" 
                    id="exec_library_path" 
                    name="library_path" 
                    class="field" 
                    value="{{ old('library_path', 'c:\wamp64\www\portal\tests\Fixtures\calibre') }}" 
                    required 
                    style="width: 100%; font-size: 13px;"
                >
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label for="import_library_id" style="display: block; font-size: 12.5px; font-weight: 600; color: var(--navy); margin-bottom: 4px;">
                        Target Digital Library
                    </label>
                    <select id="import_library_id" name="library_id" class="field" style="width: 100%; font-size: 12.5px;">
                        @foreach($libraries as $lib)
                            <option value="{{ $lib->id }}">{{ $lib->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="duplicate_mode" style="display: block; font-size: 12.5px; font-weight: 600; color: var(--navy); margin-bottom: 4px;">
                        Duplicate Resolution Mode
                    </label>
                    <select id="duplicate_mode" name="duplicate_mode" class="field" style="width: 100%; font-size: 12.5px;">
                        <option value="skip" selected>Skip (Preserve Existing Records)</option>
                        <option value="update">Update Existing with Calibre Metadata</option>
                        <option value="import_new">Import as New (Allow Duplicates)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label for="limit" style="display: block; font-size: 12px; font-weight: 600; color: var(--navy); margin-bottom: 4px;">
                        Batch Limit (Optional)
                    </label>
                    <input 
                        type="number" 
                        id="limit" 
                        name="limit" 
                        class="field" 
                        placeholder="All books" 
                        min="1" 
                        max="5000" 
                        style="width: 100%; font-size: 12.5px;"
                    >
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; justify-content: flex-end; padding-bottom: 4px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="dry_run" value="1" style="width: 15px; height: 15px;">
                        Dry-Run Simulation Only
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="run_in_background" value="1" style="width: 15px; height: 15px;">
                        Queue Background Job (Large Libraries)
                    </label>
                </div>
            </div>

            <button type="submit" class="gold" style="padding: 10px 20px; font-size: 13.5px;" onclick="return confirm('Start Calibre migration batch? This will copy files to the private vault and create portal publications.')">
                Run Migration Batch &rarr;
            </button>
        </form>
    </div>
</div>

<!-- Step 3: Secondary CSV Batch Import -->
<div class="admin-card" style="margin-bottom: 24px; border-left: 4px solid var(--navy);">
    <div class="eyebrow" style="color: var(--gold); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 6px;">Secondary Method — Structured Data</div>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;">
        <div style="max-width: 600px;">
            <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 10px; color: var(--navy);">CSV Catalogue Bulk Import</h3>
            <p style="color: var(--muted); font-size: 13px; line-height: 1.5; margin: 0 0 14px;">
                Upload a spreadsheet or CSV export. Supported columns include: <code>title</code>, <code>author</code>, <code>publisher</code>, <code>year</code>, <code>isbn</code>, <code>series</code>, <code>series_number</code>, <code>category</code>, <code>tags</code>, and <code>description</code>.
            </p>
        </div>
        <form action="{{ route('admin.imports.csv') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin: 0;">
            @csrf
            <div>
                <label for="csv_file" style="display: block; font-size: 12px; font-weight: 600; color: var(--navy); margin-bottom: 4px;">CSV File <span style="color:#c53030;">*</span></label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="field" style="font-size: 12px; padding: 6px 10px;">
            </div>
            <div>
                <label for="csv_library_id" style="display: block; font-size: 12px; font-weight: 600; color: var(--navy); margin-bottom: 4px;">Target Library</label>
                <select id="csv_library_id" name="library_id" class="field" style="font-size: 12px; padding: 7px 10px;">
                    @foreach($libraries as $lib)
                        <option value="{{ $lib->id }}">{{ $lib->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="csv_duplicate_mode" style="display: block; font-size: 12px; font-weight: 600; color: var(--navy); margin-bottom: 4px;">Duplicates</label>
                <select id="csv_duplicate_mode" name="duplicate_mode" class="field" style="font-size: 12px; padding: 7px 10px;">
                    <option value="skip">Skip</option>
                    <option value="update">Update</option>
                    <option value="import_new">Import New</option>
                </select>
            </div>
            <button type="submit" class="outline" style="padding: 8px 16px; font-size: 13px;">Upload & Import CSV &rarr;</button>
        </form>
    </div>
</div>

<!-- Import Batch History Table -->
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h3 style="font: 700 17px 'Playfair Display', serif; margin: 0; color: var(--navy);">Migration Batches History</h3>
        <span style="font-size: 12px; color: var(--muted);">Idempotent Batch Tracking</span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Source Path</th>
                    <th>Status</th>
                    <th>Detected</th>
                    <th>Imported</th>
                    <th>Duplicates</th>
                    <th>Failed</th>
                    <th>Started</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <strong style="color: var(--navy); font-size: 13px;">{{ $batch->batch_code }}</strong>
                            <small style="display: block; color: var(--muted); font-size: 11px;">By {{ $batch->user?->name ?? 'System' }}</small>
                        </td>
                        <td style="color: #64748b; font-size: 12px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $batch->source_path }}
                        </td>
                        <td>
                            @if($batch->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($batch->status === 'processing')
                                <span class="badge badge-gold">Processing</span>
                            @else
                                <span class="badge" style="background:#fee2e2; color:#991b1b;">{{ ucfirst($batch->status) }}</span>
                            @endif
                        </td>
                        <td style="font-weight: 600;">{{ $batch->total_detected }}</td>
                        <td style="color: #117838; font-weight: 700;">{{ $batch->imported_count }}</td>
                        <td style="color: var(--gold); font-weight: 600;">{{ $batch->duplicate_count }}</td>
                        <td style="color: {{ $batch->failed_count > 0 ? '#991b1b' : '#64748b' }}; font-weight: 600;">
                            {{ $batch->failed_count }}
                        </td>
                        <td style="color: var(--muted); font-size: 12px;">
                            {{ $batch->created_at->format('M d, Y H:i') }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.imports.show', $batch->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); font-weight: 600; text-decoration: underline;">
                                Report &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--muted); padding: 40px;">
                            No migration batches recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $batches->links() }}
    </div>
</div>
@endsection
