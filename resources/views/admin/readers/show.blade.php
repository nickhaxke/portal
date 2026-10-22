@extends('layouts.admin')

@section('title', 'Reader: ' . $reader->name)
@section('header_title', 'Reader Account Profile')

@section('header_actions')
    <a href="{{ route('admin.readers.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; All Readers</a>
@endsection

@section('content')
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start; margin-bottom: 24px;">
    <!-- Reader Profile Card -->
    <div class="admin-card" style="margin: 0;">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--navy); color: var(--gold); display: grid; place-items: center; font-size: 20px; font-weight: 700; flex-shrink: 0;">
                {{ strtoupper(substr($reader->name, 0, 1)) }}
            </div>
            <div>
                <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0; color: var(--navy);">{{ $reader->name }}</h3>
                <small style="color: var(--muted); font-size: 12px;">{{ $reader->email }}</small>
            </div>
        </div>

        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
            <div>
                <span style="color: #64748b; font-size: 11.5px; text-transform: uppercase; display: block;">Institutional Position</span>
                <strong style="color: var(--navy);">{{ $reader->position_title ?? 'Executive Reader' }}</strong>
            </div>

            <div>
                <span style="color: #64748b; font-size: 11.5px; text-transform: uppercase; display: block;">Organization / Affiliation</span>
                <strong style="color: var(--navy);">{{ $reader->institution ?? 'Independent' }}</strong>
            </div>

            <div>
                <span style="color: #64748b; font-size: 11.5px; text-transform: uppercase; display: block;">Verification Status</span>
                @if($reader->hasVerifiedEmail())
                    <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:4px;"><x-icon name="check" size="11" /> Verified on {{ $reader->email_verified_at->format('M d, Y') }}</span>
                @else
                    <span class="badge badge-gold">Unverified</span>
                @endif
            </div>

            <div>
                <span style="color: #64748b; font-size: 11.5px; text-transform: uppercase; display: block;">Account Status</span>
                @if(($reader->status ?? 'active') === 'active')
                    <span class="badge badge-success">Active Member</span>
                @else
                    <span class="badge" style="background:#fee2e2; color:#991b1b;">Suspended</span>
                @endif
            </div>

            <div>
                <span style="color: #64748b; font-size: 11.5px; text-transform: uppercase; display: block;">Member Since</span>
                <span style="color: var(--navy);">{{ $reader->created_at->format('F d, Y') }} ({{ $reader->created_at->diffForHumans() }})</span>
            </div>
        </div>

        <div style="border-top: 1px solid #e2e8f0; margin-top: 16px; padding-top: 14px; display: flex; flex-direction: column; gap: 8px;">
            <form action="{{ route('admin.readers.toggle-status', $reader->id) }}" method="POST">
                @csrf
                <button type="submit" class="outline" style="width: 100%; font-size: 12.5px; padding: 8px;">
                    {{ ($reader->status ?? 'active') === 'active' ? 'Suspend Account' : 'Activate Account' }}
                </button>
            </form>

            @if(!$reader->hasVerifiedEmail())
                <form action="{{ route('admin.readers.verify-email', $reader->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="gold" style="width: 100%; font-size: 12.5px; padding: 8px;">
                        Manually Verify Email
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Reading Activity & Workspace -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Reading Progress History -->
        <div class="admin-card" style="margin: 0;">
            <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 14px; color: var(--navy);">Recent Reading Progress</h4>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Publication</th>
                            <th>Page Progress</th>
                            <th>Completion</th>
                            <th>Last Read</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reader->readingProgress as $rp)
                            <tr>
                                <td>
                                    <strong style="color: var(--navy); font-size: 13.5px;">{{ $rp->book?->title ?? 'Deleted Publication' }}</strong>
                                    <small style="color: var(--muted); display: block;">By {{ $rp->book?->author_name ?? '—' }}</small>
                                </td>
                                <td style="font-size: 12.5px;">
                                    Page {{ $rp->current_page }} / {{ $rp->total_pages }}
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; background: #e2e8f0; height: 6px; border-radius: 3px; min-width: 60px;">
                                            <div style="background: var(--gold); height: 100%; width: {{ $rp->percent_complete }}%;"></div>
                                        </div>
                                        <span style="font-weight: 700; font-size: 11.5px; color: var(--navy);">{{ $rp->percent_complete }}%</span>
                                    </div>
                                </td>
                                <td style="color: var(--muted); font-size: 12px;">
                                    {{ $rp->last_read_at ? $rp->last_read_at->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--muted); padding: 25px;">
                                    No reading activity recorded for this reader yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Saved Publications & Bookmarks -->
        <div class="admin-card" style="margin: 0;">
            <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 14px; color: var(--navy);">Saved Library Publications ({{ $reader->savedBooks->count() }})</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                @forelse($reader->savedBooks as $saved)
                    <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #eef2f6; border-radius: 6px;">
                        <strong style="font-size: 13px; color: var(--navy); display: block;">{{ $saved->book?->title ?? 'Publication' }}</strong>
                        <small style="color: var(--muted); font-size: 11.5px;">Saved {{ $saved->created_at->format('M d, Y') }}</small>
                    </div>
                @empty
                    <div style="color: var(--muted); font-size: 12.5px;">
                        No books saved to My Library.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
