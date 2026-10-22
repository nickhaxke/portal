@extends('layouts.admin')

@section('title', 'Batch: ' . $import->batch_code)
@section('header_title', 'Migration Batch Report')

@section('header_actions')
    <a href="{{ route('admin.imports.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; All Batches</a>
@endsection

@section('content')
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="admin-card" style="margin: 0;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Status</small>
        <div style="margin: 8px 0 4px;">
            @if($import->status === 'completed')
                <span class="badge badge-success" style="font-size: 14px; padding: 4px 10px;">Completed</span>
            @elseif($import->status === 'processing')
                <span class="badge badge-gold" style="font-size: 14px; padding: 4px 10px;">Processing</span>
            @else
                <span class="badge" style="background:#fee2e2; color:#991b1b; font-size: 14px; padding: 4px 10px;">{{ ucfirst($import->status) }}</span>
            @endif
        </div>
        <span style="font-size: 12px; color: var(--muted);">Batch Code: {{ $import->batch_code }}</span>
    </div>

    <div class="admin-card" style="margin: 0;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Imported</small>
        <div style="font: 700 32px 'Playfair Display', serif; color: #117838; margin: 4px 0 2px;">{{ $import->imported_count }}</div>
        <span style="font-size: 12px; color: var(--muted);">Of {{ $import->total_detected }} detected</span>
    </div>

    <div class="admin-card" style="margin: 0;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Duplicates Skipped</small>
        <div style="font: 700 32px 'Playfair Display', serif; color: var(--gold); margin: 4px 0 2px;">{{ $import->duplicate_count }}</div>
        <span style="font-size: 12px; color: var(--muted);">Preserved data integrity</span>
    </div>

    <div class="admin-card" style="margin: 0;">
        <small style="color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700;">Failed Items</small>
        <div style="font: 700 32px 'Playfair Display', serif; color: {{ $import->failed_count > 0 ? '#991b1b' : '#64748b' }}; margin: 4px 0 2px;">{{ $import->failed_count }}</div>
        <span style="font-size: 12px; color: var(--muted);">Errors during import</span>
    </div>
</div>

<div class="admin-card" style="margin-bottom: 24px;">
    <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 12px; color: var(--navy);">Batch Details</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; font-size: 13px;">
        <div>
            <strong style="color: #64748b; display: block; font-size: 11.5px; text-transform: uppercase;">Source Type</strong>
            <span style="color: var(--navy); font-weight: 600;">{{ strtoupper($import->source_type) }}</span>
        </div>
        <div>
            <strong style="color: #64748b; display: block; font-size: 11.5px; text-transform: uppercase;">Source Path</strong>
            <span style="color: var(--navy); font-family: monospace; font-size: 12px;">{{ $import->source_path }}</span>
        </div>
        <div>
            <strong style="color: #64748b; display: block; font-size: 11.5px; text-transform: uppercase;">Executed By</strong>
            <span style="color: var(--navy); font-weight: 600;">{{ $import->user?->name ?? 'Console Command' }}</span>
        </div>
        <div>
            <strong style="color: #64748b; display: block; font-size: 11.5px; text-transform: uppercase;">Created At</strong>
            <span style="color: var(--navy);">{{ $import->created_at->format('M d, Y H:i:s') }}</span>
        </div>
    </div>
</div>

<!-- Detailed Logs -->
<div class="admin-card">
    <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Import Execution Log Trail</h4>
    @php
        $logs = $import->summary_report['logs'] ?? [];
    @endphp

    @if(empty($logs))
        <div style="text-align: center; color: var(--muted); padding: 30px 0; font-size: 13px;">
            No item logs recorded for this batch.
        </div>
    @else
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Status</th>
                        <th>Calibre ID</th>
                        <th>Publication Title</th>
                        <th>Details / Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>
                                @if(($log['status'] ?? '') === 'imported')
                                    <span class="badge badge-success" style="font-size: 10px;">Imported</span>
                                @elseif(($log['status'] ?? '') === 'duplicate')
                                    <span class="badge badge-gold" style="font-size: 10px;">Duplicate</span>
                                @elseif(($log['status'] ?? '') === 'dry_run_ready')
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px;">Dry Run</span>
                                @else
                                    <span class="badge" style="background:#fee2e2; color:#991b1b; font-size: 10px;">Failed</span>
                                @endif
                            </td>
                            <td style="font-weight: 700; color: #64748b;">#{{ $log['calibre_id'] ?? '—' }}</td>
                            <td><strong style="color: var(--navy);">{{ $log['title'] ?? '—' }}</strong></td>
                            <td style="font-size: 12px; color: var(--muted);">
                                {{ $log['message'] ?? ($log['error'] ?? (isset($log['files_count']) ? "Attached {$log['files_count']} format files to vault" : '')) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
