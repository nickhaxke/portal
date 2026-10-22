@extends('layouts.admin')

@section('title', 'Edit Library — ' . $library->name)
@section('header_title', 'Edit Library')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <a href="{{ route('admin.libraries.index') }}" class="plain" style="font-size: 13px; color: #64748b;">
            &larr; Back to Libraries
        </a>
        <a href="{{ route('admin.libraries.show', $library->id) }}" class="outline" style="padding: 6px 14px; font-size: 12.5px; text-decoration: none;">
            Manage Books ({{ $library->books()->count() }}) &rarr;
        </a>
    </div>

    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0 0 6px; color: var(--navy); font-size: 22px;">Edit Library: {{ $library->name }}</h2>
                <small style="color: #64748b; font-family: monospace;">Slug: {{ $library->slug }}</small>
            </div>
            <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 4px; background: {{ $library->is_published ? '#e6f9ed' : '#f1f5f9' }}; color: {{ $library->is_published ? '#117838' : '#64748b' }};">
                {{ $library->is_published ? 'Published' : 'Draft' }}
            </span>
        </div>

        <form action="{{ route('admin.libraries.update', $library->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Library Name <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $library->name) }}" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                @error('name')
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Description -->
            <div style="margin-bottom: 20px;">
                <label for="description" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Description & Scope
                </label>
                <textarea id="description" name="description" rows="4" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; line-height: 1.5;">{{ old('description', $library->description) }}</textarea>
                @error('description')
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Icon & Visibility Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label for="icon" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Icon Identifier
                    </label>
                    <select id="icon" name="icon" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white;">
                        <option value="library" {{ old('icon', $library->icon) === 'library' ? 'selected' : '' }}>Library Emblem</option>
                        <option value="publications" {{ old('icon', $library->icon) === 'publications' ? 'selected' : '' }}>Publications</option>
                        <option value="strategy" {{ old('icon', $library->icon) === 'strategy' ? 'selected' : '' }}>Strategy / Chess</option>
                        <option value="shield" {{ old('icon', $library->icon) === 'shield' ? 'selected' : '' }}>Governance / Shield</option>
                        <option value="chart" {{ old('icon', $library->icon) === 'chart' ? 'selected' : '' }}>Economics / Analytics</option>
                        <option value="academic" {{ old('icon', $library->icon) === 'academic' ? 'selected' : '' }}>Academic / Research</option>
                        <option value="overview" {{ old('icon', $library->icon) === 'overview' ? 'selected' : '' }}>Intelligence</option>
                    </select>
                </div>

                <div>
                    <label for="visibility" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Access Policy <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="visibility" name="visibility" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white;">
                        <option value="public" {{ old('visibility', $library->visibility) === 'public' ? 'selected' : '' }}>Public (Visible to all visitors)</option>
                        <option value="restricted" {{ old('visibility', $library->visibility) === 'restricted' ? 'selected' : '' }}>Restricted (Registered Readers only)</option>
                        <option value="private" {{ old('visibility', $library->visibility) === 'private' ? 'selected' : '' }}>Private (Administrators only)</option>
                    </select>
                </div>
            </div>

            <!-- Sort Order & Publishing Status -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label for="sort_order" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Display Sort Order
                    </label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $library->sort_order) }}" min="0" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                </div>

                <div style="display: flex; align-items: center; padding-top: 25px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13.5px; font-weight: 600; color: var(--navy);">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $library->is_published) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--gold);">
                        <span>Publish Library</span>
                    </label>
                </div>
            </div>

            <!-- Cover Image / Banner -->
            <div style="margin-bottom: 30px;">
                <label for="cover_image" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Library Banner / Artwork
                </label>
                @if($library->cover_image)
                    <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 14px;">
                        <img src="{{ $library->cover_url }}" alt="{{ $library->name }}" style="max-height: 80px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <small style="color: #64748b;">Current artwork stored. Upload below to replace.</small>
                    </div>
                @endif
                <input type="file" id="cover_image" name="cover_image" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
            </div>

            <!-- Submit Buttons -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="{{ route('admin.libraries.index') }}" class="outline" style="padding: 10px 20px; font-size: 13.5px; text-decoration: none;">
                    Cancel
                </a>
                <button type="submit" class="gold" style="padding: 10px 24px; font-size: 13.5px; cursor: pointer;">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
