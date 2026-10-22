@extends('layouts.admin')

@section('title', 'Edit: ' . $insight->title)
@section('header_title', 'Edit Executive Insight')

@section('header_actions')
    <a href="{{ route('insights.show', $insight->slug) }}" target="_blank" class="outline" style="padding: 6px 14px; font-size: 13px;">
        Preview Live ↗
    </a>
@endsection

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.insights.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Insights</a>
</div>

<div class="admin-card" style="max-width: 860px;" x-data="{
    previewUrl: @js($insight->featuredImageUrl()),
    removeImage: false,
    handleFile(e) {
        const file = e.target.files[0];
        if (file) {
            this.previewUrl = URL.createObjectURL(file);
            this.removeImage = false;
        }
    }
}">
    <h3 style="font: 700 20px 'Playfair Display', serif; margin: 0 0 20px; color: var(--navy);">Edit: {{ $insight->title }}</h3>

    <form action="{{ route('admin.insights.update', $insight->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label for="title" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Insight Title <span style="color:#c53030;">*</span>
                </label>
                <input type="text" id="title" name="title" class="field" value="{{ old('title', $insight->title) }}" required style="width: 100%; font-size: 15px;">
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1.3fr 1fr; gap: 16px; align-items: start;">
                <div>
                    <label for="category_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Editorial Theme <span style="color:#c53030;">*</span>
                    </label>
                    <input type="text" id="category_name" name="category_name" class="field" value="{{ old('category_name', $insight->category_name) }}" required style="width: 100%;">
                </div>
                <div>
                    <x-icon-picker name="icon" :value="old('icon', $insight->icon_name)" label="System Icon" />
                </div>
                <div>
                    <label for="read_time" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">Read Length</label>
                    <input type="text" id="read_time" name="read_time" class="field" value="{{ old('read_time', $insight->read_time) }}" style="width: 100%;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                <div>
                    <label for="author_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">Author / Institutional Attribution</label>
                    <input type="text" id="author_name" name="author_name" class="field" value="{{ old('author_name', $insight->author_name) }}" style="width: 100%;">
                </div>
                <div>
                    <label for="sort_order" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" class="field" value="{{ old('sort_order', $insight->sort_order) }}" style="width: 100%;">
                </div>
            </div>

            <!-- Featured Image -->
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Editorial Featured Image (Landscape 16:9 or 3:2)
                </label>
                <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; background: #fafaf9; text-align: center;">
                    <template x-if="previewUrl && !removeImage">
                        <div style="margin: 0 auto 14px; max-width: 480px;">
                            <img :src="previewUrl" alt="Current featured image" style="width: 100%; height: 220px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        </div>
                    </template>
                    <template x-if="!previewUrl || removeImage">
                        <div style="margin: 0 auto 14px; max-width: 480px; height: 120px; border-radius: 8px; background: linear-gradient(135deg, #102946, #1d3d63); color: #d9b46a; display: flex; align-items: center; justify-content: center; font: 600 13px 'DM Sans', sans-serif; letter-spacing: 0.5px;">
                            Typographic editorial fallback will be shown
                        </div>
                    </template>
                    <label for="image" style="display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px;">
                        {{ $insight->featured_image ? 'Upload a new image to replace the current one:' : 'Upload an editorial image:' }}
                    </label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" @change="handleFile($event)" style="font-size: 13px;">
                    @if($insight->featured_image)
                        <label style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: #991b1b; cursor: pointer; margin-top: 10px;">
                            <input type="checkbox" name="remove_image" value="1" x-model="removeImage">
                            Remove current image
                        </label>
                    @endif
                </div>
            </div>

            <div>
                <label for="image_alt" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Image Alt Text <small style="font-weight: 400; color: var(--muted);">(Optional)</small>
                </label>
                <input type="text" id="image_alt" name="image_alt" class="field" value="{{ old('image_alt', $insight->image_alt) }}" style="width: 100%;">
            </div>

            <div>
                <label for="excerpt" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Executive Summary / Excerpt
                </label>
                <textarea id="excerpt" name="excerpt" rows="3" class="field" style="width: 100%; line-height: 1.5;">{{ old('excerpt', $insight->excerpt) }}</textarea>
            </div>

            <div>
                <label for="content" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Insight Body & Editorial Analysis <span style="color:#c53030;">*</span>
                </label>
                <textarea id="content" name="content" rows="12" class="field" required style="width: 100%; line-height: 1.6;">{{ old('content', $insight->content) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $insight->published_at !== null) ? 'checked' : '' }} style="width: 16px; height: 16px;">
                        Published on portal
                    </label>
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                        @if($insight->published_at)
                            Published {{ $insight->published_at->format('d M Y') }}
                        @else
                            Currently a draft
                        @endif
                    </small>
                </div>
                <div style="padding: 12px 16px; background: #fffdf5; border: 1px solid #fae8b8; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $insight->is_featured) ? 'checked' : '' }} style="width: 16px; height: 16px;">
                        Feature on homepage
                    </label>
                </div>
            </div>

            <div style="margin-top: 5px;">
                <button type="submit" class="gold" style="padding: 12px 28px; font-size: 14px;">
                    Update Executive Insight &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
