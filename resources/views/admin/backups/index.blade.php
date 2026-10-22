@extends('layouts.admin')

@section('title', 'System & Database Backups')
@section('header_title', 'System Operations & Database Backups')

@section('header_actions')
    <form action="{{ route('admin.backups.create') }}" method="POST" style="margin:0;">
        @csrf
        <button type="submit" class="gold" style="padding: 8px 16px; font-size: 13px; border:0; cursor:pointer;" onclick="return confirm('Create a new live database snapshot now?')">
            + Create Backup Snapshot
        </button>
    </form>
@endsection

@section('content')
<!-- Safety Alert Banner -->
<div class="admin-card" style="background: #fffdf5; border-left: 4px solid var(--gold); margin-bottom: 24px;">
    <h4 style="color: #78350f; font-size: 15px; margin: 0 0 6px;">Enterprise Disaster Recovery & Data Safeguards</h4>
    <p style="color: #92400e; font-size: 13px; line-height: 1.5; margin: 0;">
        Database snapshots are stored securely inside <code>database/backups/</code> outside the public web document root.
        To prevent accidental destruction of production data, restoration requires direct administrative console execution.
    </p>
</div>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0; color: var(--navy);">Available Database Snapshots</h3>
        <span style="font-size: 12.5px; color: var(--muted);">Stored on Server Disk</span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Backup Snapshot File</th>
                    <th>File Size</th>
                    <th>Created Timestamp</th>
                    <th>Storage Location</th>
                    <th style="text-align: right;">Restoration Command</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $b)
                    <tr>
                        <td>
                            <strong style="color: var(--navy); font-size: 13.5px; font-family: monospace;">{{ $b['name'] }}</strong>
                        </td>
                        <td>
                            <span class="badge badge-navy" style="font-size: 11px; font-weight: 700;">
                                {{ $b['size_formatted'] }}
                            </span>
                        </td>
                        <td style="color: #334155; font-size: 13px;">
                            {{ $b['modified_at'] }}
                        </td>
                        <td style="color: var(--muted); font-size: 12px; font-family: monospace;">
                            database/backups/{{ $b['name'] }}
                        </td>
                        <td style="text-align: right;">
                            <code style="background: #0f172a; color: #86efac; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                                mysql -u root -p portal_db &lt; {{ $b['name'] }}
                            </code>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--muted); padding: 40px;">
                            No database backups found in <code>database/backups/</code>. Click "+ Create Backup Snapshot" above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
