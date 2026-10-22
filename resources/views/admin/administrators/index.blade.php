@extends('layouts.admin')

@section('title', 'Administrators & Access Roles')
@section('header_title', 'Administrative Governance & Roles')

@section('header_actions')
    <a href="{{ route('admin.administrators.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none;">
        + Add Administrator
    </a>
@endsection

@section('content')
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0; color: var(--navy);">Administrative Accounts & Governance</h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">Staff credentials, role assignments, and curatorial permissions.</p>
        </div>
        <span style="font-size: 13px; color: var(--muted);">
            Active Staff: <strong>{{ $admins->count() }}</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Staff Name & Email</th>
                    <th>Role & Authority</th>
                    <th>Position Title</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                    <tr>
                        <td>
                            <strong style="color: var(--navy); font-size: 14px; display: block;">
                                {{ $admin->name }}
                                @if($admin->id === auth()->id())
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px; margin-left:4px;">You</span>
                                @endif
                            </strong>
                            <small style="color: #64748b; font-size: 12px;">{{ $admin->email }}</small>
                        </td>
                        <td>
                            @if($admin->role === 'superadmin')
                                <span class="badge" style="background:#1e1b4b; color:#e0e7ff; font-weight:700;">Superadmin</span>
                            @elseif($admin->role === 'admin')
                                <span class="badge badge-navy">Library Manager</span>
                            @elseif($admin->role === 'editor')
                                <span class="badge badge-gold">Content Editor</span>
                            @else
                                <span class="badge" style="background:#f1f5f9; color:#475569;">Viewer</span>
                            @endif
                        </td>
                        <td style="color: #475569; font-size: 13px;">
                            {{ $admin->position_title ?? 'Administrator' }}
                        </td>
                        <td>
                            @if(($admin->status ?? 'active') === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge" style="background:#fee2e2; color:#991b1b;">Suspended</span>
                            @endif
                        </td>
                        <td style="color: var(--muted); font-size: 12px;">
                            {{ $admin->created_at->format('M d, Y') }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('admin.administrators.edit', $admin->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); font-weight: 600; text-decoration: underline;">
                                    Edit
                                </a>

                                @if($admin->id !== auth()->id())
                                    <form action="{{ route('admin.administrators.destroy', $admin->id) }}" method="POST" onsubmit="return confirm('Remove administrator account for \'{{ addslashes($admin->name) }}\'?')" style="margin:0; display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none; border:0; color:#991b1b; font-size:12.5px; cursor:pointer; padding:0;">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
