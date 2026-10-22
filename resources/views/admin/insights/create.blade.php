@extends('layouts.admin')

@section('title', 'Create Executive Insight')
@section('header_title', 'Create Executive Insight & Briefing')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.insights.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Insights</a>
</div>

<div class="admin-card" style="max-width: 860px;" x-data="{
    title: @js(old('title', '')),
    categoryName: @js(old('category_name', 'Leadership')),
    readTime: @js(old('read_time', '5 min read')),
    authorName: @js(old('author_name', 'Executive Editorial Board')),
    previewUrl: null,
    handleFile(e) {
        const file = e.target.files[0];
        if (file) {
            this.previewUrl = URL.createObjectURL(file);
        } else {
            this.previewUrl = null;
        }
    }
}">
    <h3 style="font: 700 20px 'Playfair Display', serif; margin: 0 0 20px; color: var(--navy);">Editorial Content & Media Artwork</h3>

    <form action="{{ route('admin.insights.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Title -->
            <div>
                <label for="title" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Insight Title <span style="color:#c53030;">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    x-model="title"
                    value="{{ old('title') }}" 
                    class="field" 
                    placeholder="e.g. Cognitive Agility in Multilateral Boardrooms" 
                    required 
                    style="width: 100%; font-size: 15px;"
                >
            </div>

            <!-- Balanced 3-Column Meta Row -->
            <div style="display: grid; grid-template-columns: 1.5fr 1.3fr 1fr; gap: 16px; align-items: start;">
                <div>
                    <label for="category_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Editorial Theme <span style="color:#c53030;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="category_name" 
                        name="category_name" 
                        x-model="categoryName"
                        class="field" 
                        value="{{ old('category_name', 'Leadership') }}" 
                        placeholder="Leadership, Strategy" 
                        required 
                        style="width: 100%;"
                    >
                </div>

                <div>
                    <x-icon-picker 
                        name="icon" 
                        :value="old('icon', 'strategy')" 
                        label="System Icon"
                    />
                </div>

                <div>
                    <label for="read_time" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Read Length
                    </label>
                    <input 
                        type="text" 
                        id="read_time" 
                        name="read_time" 
                        x-model="readTime"
                        class="field" 
                        value="{{ old('read_time', '5 min read') }}" 
                        style="width: 100%;"
                    >
                </div>
            </div>

            <!-- Author Attribution & Display Order -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                <div>
                    <label for="author_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Author / Institutional Attribution
                    </label>
                    <input 
                        type="text" 
                        id="author_name" 
                        name="author_name" 
                        x-model="authorName"
                        value="{{ old('author_name', 'Executive Editorial Board') }}" 
                        class="field" 
                        placeholder="e.g. Dr. Evelyn Vance or Executive Editorial Board"
                        style="width: 100%;"
                    >
                </div>

                <div>
                    <label for="sort_order" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Sort Order
                    </label>
                    <input 
                        type="number" 
                        id="sort_order" 
                        name="sort_order" 
                        value="{{ old('sort_order', $nextOrder) }}" 
                        min="0" 
                        class="field" 
                        style="width: 100%;"
                    >
                </div>
            </div>

            <!-- Featured Image Upload & Live Preview -->
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Editorial Featured Image (Landscape 16:9 or 3:2)
                </label>
                <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; background: #fafaf9; text-align: center;">
                    <template x-if="previewUrl">
                        <div style="margin-bottom: 14px; max-width: 480px; margin-left: auto; margin-right: auto;">
                            <img :src="previewUrl" alt="Preview" style="width: 100%; height: 220px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        </div>
                    </template>
                    <input 
                        type="file" 
                        id="image" 
                        name="image" 
                        accept="image/jpeg,image/png,image/webp" 
                        @change="handleFile($event)"
                        style="font-size: 13px;"
                    >
                    <small style="display: block; color: var(--muted); margin-top: 6px; font-size: 11.5px;">
                        Optional editorial photograph. If omitted, a bespoke executive typographic cover will be presented. JPEG, PNG, WebP up to 5MB.
                    </small>
                </div>
            </div>

            <!-- Image Alt Text -->
            <div>
                <label for="image_alt" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Image Alt Text / Accessible Description <small style="font-weight: 400; color: var(--muted);">(Optional)</small>
                </label>
                <input 
                    type="text" 
                    id="image_alt" 
                    name="image_alt" 
                    value="{{ old('image_alt') }}" 
                    class="field" 
                    placeholder="e.g. Multilateral executive summit in modern boardroom"
                    style="width: 100%;"
                >
            </div>

            <!-- Executive Excerpt -->
            <div>
                <label for="excerpt" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Executive Summary / Excerpt <small style="font-weight: 400; color: var(--muted);">(Shown on editorial card feeds)</small>
                </label>
                <textarea 
                    id="excerpt" 
                    name="excerpt" 
                    rows="3" 
                    class="field" 
                    placeholder="A concise 1–2 sentence strategic takeaway for busy executive readers..." 
                    style="width: 100%; line-height: 1.5;"
                >{{ old('excerpt') }}</textarea>
            </div>

            <!-- Body Content -->
            <div>
                <label for="content" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Insight Body & Editorial Analysis <span style="color:#c53030;">*</span>
                </label>
                <textarea 
                    id="content" 
                    name="content" 
                    rows="12" 
                    class="field" 
                    placeholder="Provide executive analysis, critical takeaways, and institutional frameworks..." 
                    required 
                    style="width: 100%; line-height: 1.6;"
                >{{ old('content') }}</textarea>
            </div>

            <!-- Publication & Featured Checkboxes -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }} style="width: 16px; height: 16px;">
                        Publish immediately to portal
                    </label>
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                        Unchecked: Saves as internal editorial draft.
                    </small>
                </div>

                <div style="padding: 12px 16px; background: #fffdf5; border: 1px solid #fae8b8; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} style="width: 16px; height: 16px;">
                        Feature prominently on homepage
                    </label>
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                        Highlights this article on the executive landing page.
                    </small>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 12px 28px; font-size: 14px;">
                    Create Executive Insight &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
