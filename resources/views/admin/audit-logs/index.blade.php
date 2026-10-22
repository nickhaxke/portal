@extends('layouts.admin')

@section('title', 'Activity & Mutation Audit Logs')
@section('header_title', 'Administrative Activity & Audit Trail')

@section('content')
<div class="admin-card">
    <!-- Filter bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" style="display: flex; gap: 10px; flex: 1; max-width: 650px; flex-wrap: wrap;">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Search action, IP address or entity..." 
                class="field" 
                style="flex: 1; min-width: 220px; font-size: 13px;"
            >

            <select name="user_id" class="field" style="width: 170px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Administrators</option>
                @foreach($admins as $adminUser)
                    <option value="{{ $adminUser->id }}" {{ $userId == $adminUser->id ? 'selected' : '' }}>
                        {{ $adminUser->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="outline" style="padding: 8px 16px;">Filter</button>
            @if($search || $userId || $action)
                <a href="{{ route('admin.audit-logs.index') }}" class="plain" style="align-self: center; font-size: 12px; color: var(--muted);">Reset</a>
            @endif
        </form>

        <span style="font-size: 12.5px; color: var(--muted);">
            Retention: <strong>Immutable Write-Only Trail</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Staff User</th>
                    <th>Action Executed</th>
                    <th>Target Entity</th>
                    <th>IP / User-Agent</th>
                    <th style="text-align: right;">Mutation Diffs</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="color: var(--navy); font-size: 12.5px; font-weight: 600; white-space: nowrap;">
                            {{ $log->created_at->format('M d, Y H:i:s') }}
                            <small style="display: block; color: #94a3b8; font-size: 11px;">{{ $log->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            <strong style="color: var(--navy); font-size: 13px; display: block;">{{ $log->user?->name ?? 'System Process' }}</strong>
                            <small style="color: var(--muted); font-size: 11px;">{{ $log->user?->email ?? 'Automated' }}</small>
                        </td>
                        <td>
                            <span class="badge" style="background: #0f172a; color: #f8fafc; font-family: monospace; font-size: 11px;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 12.5px; color: #334155; font-weight: 600; display: block;">
                                {{ class_basename($log->auditable_type ?? 'Entity') }} #{{ $log->auditable_id ?? 'N/A' }}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 11.5px;">
                            <span>{{ $log->ip_address ?? '127.0.0.1' }}</span>
                            <small style="display: block; color: #94a3b8; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $log->user_agent ?? 'Internal' }}
                            </small>
                        </td>
                        <td style="text-align: right;">
                            <details style="text-align: left; display: inline-block;">
                                <summary style="cursor: pointer; color: var(--gold); font-size: 12px; font-weight: 600;">
                                    View Diff JSON
                                </summary>
                                <div style="position: absolute; right: 40px; background: #0f172a; color: #f8fafc; padding: 14px; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); max-width: 450px; font-size: 11px; z-index: 50; overflow-x: auto;">
                                    @if($log->old_values)
                                        <div style="color: #fca5a5; margin-bottom: 6px;">
                                            <strong>Old Values:</strong>
                                            <pre style="margin: 4px 0;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    @endif
                                    @if($log->new_values)
                                        <div style="color: #86efac;">
                                            <strong>New Values:</strong>
                                            <pre style="margin: 4px 0;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    @endif
                                    @if(!$log->old_values && !$log->new_values)
                                        <em style="color: #94a3b8;">No state changes recorded.</em>
                                    @endif
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted); padding: 40px;">
                            No audit log events recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
