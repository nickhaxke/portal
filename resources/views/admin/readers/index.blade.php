@extends('layouts.admin')

@section('title', 'Readers & Membership Administration')
@section('header_title', 'Executive Readers & Membership Control')

@section('content')
<div class="admin-card">
    <!-- Filter bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="{{ route('admin.readers.index') }}" style="display: flex; gap: 10px; flex: 1; max-width: 600px; flex-wrap: wrap;">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Search by name, email, institution or title..." 
                class="field" 
                style="flex: 1; min-width: 220px; font-size: 13px;"
            >

            <select name="status" class="field" style="width: 130px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>

            <select name="verified" class="field" style="width: 140px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Verifications</option>
                <option value="1" {{ $verified === '1' ? 'selected' : '' }}>Email Verified</option>
                <option value="0" {{ $verified === '0' ? 'selected' : '' }}>Unverified</option>
            </select>

            <button type="submit" class="outline" style="padding: 8px 16px;">Filter</button>
            @if($search || $status || $verified !== null)
                <a href="{{ route('admin.readers.index') }}" class="plain" style="align-self: center; font-size: 12px; color: var(--muted);">Reset</a>
            @endif
        </form>

        <span style="font-size: 13px; color: var(--muted);">
            Total Reader Accounts: <strong>{{ $readers->total() }}</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Reader Profile</th>
                    <th>Institutional Role</th>
                    <th>Verification</th>
                    <th>Account Status</th>
                    <th>Engagement Metrics</th>
                    <th>Registered</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($readers as $reader)
                    <tr>
                        <td>
                            <strong style="color: var(--navy); font-size: 14px; display: block;">{{ $reader->name }}</strong>
                            <small style="color: #64748b; font-size: 12px;">{{ $reader->email }}</small>
                        </td>
                        <td>
                            <span style="font-size: 13px; color: var(--navy); font-weight: 600; display: block;">
                                {{ $reader->position_title ?? 'Executive Reader' }}
                            </span>
                            <small style="color: var(--muted); font-size: 11.5px;">{{ $reader->institution ?? 'Independent' }}</small>
                        </td>
                        <td>
                            @if($reader->hasVerifiedEmail())
                                <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:4px;"><x-icon name="check" size="11" /> Verified</span>
                            @else
                                <span class="badge badge-gold">Unverified</span>
                            @endif
                        </td>
                        <td>
                            @if(($reader->status ?? 'active') === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge" style="background:#fee2e2; color:#991b1b;">Suspended</span>
                            @endif
                        </td>
                        <td style="font-size: 12.5px; color: #475569;">
                            <span>{{ $reader->reading_progress_count }} reading sessions</span> &bull;
                            <span>{{ $reader->saved_books_count }} saved</span>
                        </td>
                        <td style="color: var(--muted); font-size: 12px;">
                            {{ $reader->created_at->format('M d, Y') }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('admin.readers.show', $reader->id) }}" class="plain" style="font-size: 12.5px; color: var(--navy); font-weight: 600; text-decoration: underline;">
                                    Profile
                                </a>

                                <form action="{{ route('admin.readers.toggle-status', $reader->id) }}" method="POST" style="margin:0; display:inline;">
                                    @csrf
                                    <button type="submit" style="background:none; border:0; color: {{ ($reader->status ?? 'active') === 'active' ? '#991b1b' : '#117838' }}; font-size:12px; cursor:pointer; padding:0;">
                                        {{ ($reader->status ?? 'active') === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>

                                @if(!$reader->hasVerifiedEmail())
                                    <form action="{{ route('admin.readers.verify-email', $reader->id) }}" method="POST" style="margin:0; display:inline;">
                                        @csrf
                                        <button type="submit" style="background:none; border:0; color:var(--gold); font-size:12px; font-weight:600; cursor:pointer; padding:0;">
                                            Verify
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--muted); padding: 40px;">
                            No reader accounts match this criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $readers->links() }}
    </div>
</div>
@endsection
