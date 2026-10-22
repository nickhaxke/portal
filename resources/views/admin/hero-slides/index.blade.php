@extends('layouts.admin')

@section('title', 'Hero Slides & Editorial Slider')
@section('header_title', 'Homepage Hero Slider Management')

@section('header_actions')
    <a href="{{ route('admin.hero-slides.create') }}" class="gold" style="font-size: 13.5px; padding: 8px 16px; text-decoration: none;">
        + Add New Slide
    </a>
@endsection

@section('content')
<div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <p style="color: var(--muted); font-size: 13.5px; margin: 0;">
            Manage curated editorial hero slides featured on the public homepage. Only active slides appear on the portal.
        </p>
    </div>
    <a href="{{ route('home') }}" target="_blank" class="outline" style="font-size: 12.5px; padding: 6px 14px; text-decoration: none;">
        Preview Homepage ↗
    </a>
</div>

<div class="admin-card" style="padding: 0; overflow: hidden;">
    @if($slides->count() > 0)
        <div class="table-responsive" tabindex="0" role="region" aria-label="Hero slides; scroll horizontally to reach actions">
        <table class="admin-table hero-management-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Order</th>
                    <th style="width: 160px;">Slide Artwork</th>
                    <th>Headline & Content</th>
                    <th style="width: 180px;">CTA Destination</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slides as $slide)
                    <tr>
                        <td style="font-weight: 700; color: var(--navy); font-size: 14px;">
                            #{{ $slide->sort_order }}
                        </td>
                        <td>
                            <div style="width: 140px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; background: #102946; position: relative;">
                                <img 
                                    src="{{ $slide->imageUrl() }}" 
                                    alt="{{ $slide->title }}" 
                                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                    loading="lazy"
                                >
                            </div>
                        </td>
                        <td>
                            <strong style="font-size: 14.5px; color: var(--navy); display: block; font-family: 'Playfair Display', serif;">
                                {{ $slide->title }}
                            </strong>
                            @if($slide->subtitle)
                                <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0; line-height: 1.4; max-width: 420px;">
                                    {{ Str::limit($slide->subtitle, 110) }}
                                </p>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 12px; font-weight: 600; color: var(--navy);">
                                {{ $slide->cta_text }}
                            </div>
                            <code style="font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 3px;">
                                {{ $slide->cta_url }}
                            </code>
                        </td>
                        <td>
                            <form action="{{ route('admin.hero-slides.toggle', $slide->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="badge {{ $slide->is_active ? 'badge-published' : 'badge-draft' }}" 
                                    style="border: none; cursor: pointer;"
                                    title="Click to toggle status"
                                >
                                    <x-icon name="{{ $slide->is_active ? 'check' : 'close' }}" size="11" />
                                    {{ $slide->is_active ? 'Active' : 'Disabled' }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('admin.hero-slides.edit', $slide->id) }}" class="outline" style="padding: 4px 10px; font-size: 12px; text-decoration: none;">
                                    Edit
                                </a>
                                <form action="{{ route('admin.hero-slides.destroy', $slide->id) }}" method="POST" onsubmit="return confirm('Permanently remove this hero slide?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger" style="padding: 4px 10px; font-size: 12px;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div style="padding: 50px 20px; text-align: center;">
            <div style="color: var(--gold); margin-bottom: 12px;"><x-icon name="explore" size="42" /></div>
            <h3 style="font: 700 18px 'Playfair Display', serif; color: var(--navy); margin: 0 0 8px;">No Hero Slides Configured</h3>
            <p style="color: var(--muted); font-size: 13.5px; max-width: 450px; margin: 0 auto 20px;">
                Create your first curated editorial slide to feature high-resolution artwork and strategic headlines on the portal landing page.
            </p>
            <a href="{{ route('admin.hero-slides.create') }}" class="gold" style="font-size: 13.5px; padding: 10px 20px; text-decoration: none;">
                + Create Initial Slide
            </a>
        </div>
    @endif
</div>
@endsection
