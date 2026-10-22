@extends('layouts.admin')

@section('title', 'Edit Hero Slide: ' . $heroSlide->title)
@section('header_title', 'Edit Executive Hero Slide')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.hero-slides.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Hero Slides</a>
</div>

<div class="admin-card" style="max-width: 820px;" x-data="{
    title: @js(old('title', $heroSlide->title)),
    subtitle: @js(old('subtitle', $heroSlide->subtitle ?? '')),
    ctaText: @js(old('cta_text', $heroSlide->cta_text)),
    ctaUrl: @js(old('cta_url', $heroSlide->cta_url)),
    previewUrl: @js($heroSlide->imageUrl()),
    handleFile(e) {
        const file = e.target.files[0];
        if (file) {
            this.previewUrl = URL.createObjectURL(file);
        }
    }
}">
    <h3 style="font: 700 20px 'Playfair Display', serif; margin: 0 0 20px; color: var(--navy);">Edit Slide: {{ $heroSlide->title }}</h3>

    <form action="{{ route('admin.hero-slides.update', $heroSlide->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Title -->
            <div>
                <label for="title" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Slide Headline <span style="color:#c53030;">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    x-model="title"
                    value="{{ old('title', $heroSlide->title) }}" 
                    class="field" 
                    required 
                    style="width: 100%; font-size: 15px;"
                >
            </div>

            <!-- Subtitle -->
            <div>
                <label for="subtitle" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Slide Caption / Executive Briefing <small style="font-weight: 400; color: var(--muted);">(Optional)</small>
                </label>
                <textarea 
                    id="subtitle" 
                    name="subtitle" 
                    x-model="subtitle"
                    rows="3" 
                    class="field" 
                    style="width: 100%; line-height: 1.5;"
                >{{ old('subtitle', $heroSlide->subtitle) }}</textarea>
            </div>

            <!-- Image Upload & Live Preview -->
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Slide Artwork (Landscape 16:9 or 16:10)
                </label>
                <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; background: #fafaf9; text-align: center;">
                    <div style="margin-bottom: 14px; max-width: 480px; margin-left: auto; margin-right: auto;">
                        <img :src="previewUrl" alt="Preview" style="width: 100%; height: 240px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    </div>
                    <label for="image" style="display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px;">
                        Upload new artwork to replace current image:
                    </label>
                    <input 
                        type="file" 
                        id="image" 
                        name="image" 
                        accept="image/jpeg,image/png,image/webp" 
                        @change="handleFile($event)"
                        style="font-size: 13px;"
                    >
                </div>
            </div>

            <!-- CTA Destination & Sort Order -->
            <div style="display: grid; grid-template-columns: 1.5fr 1.5fr 0.8fr; gap: 16px;">
                <div>
                    <label for="cta_text" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        CTA Button Label
                    </label>
                    <input 
                        type="text" 
                        id="cta_text" 
                        name="cta_text" 
                        x-model="ctaText"
                        value="{{ old('cta_text', $heroSlide->cta_text) }}" 
                        class="field" 
                        style="width: 100%;"
                    >
                </div>

                <div>
                    <label for="cta_url" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        CTA Destination URL / Path
                    </label>
                    <input 
                        type="text" 
                        id="cta_url" 
                        name="cta_url" 
                        x-model="ctaUrl"
                        value="{{ old('cta_url', $heroSlide->cta_url) }}" 
                        class="field" 
                        style="width: 100%;"
                    >
                </div>

                <div>
                    <label for="sort_order" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Display Order
                    </label>
                    <input 
                        type="number" 
                        id="sort_order" 
                        name="sort_order" 
                        value="{{ old('sort_order', $heroSlide->sort_order) }}" 
                        min="0"
                        class="field" 
                        style="width: 100%;"
                    >
                </div>
            </div>

            <!-- Active Toggle -->
            <div style="padding: 12px 16px; background: #fffdf5; border: 1px solid #fae8b8; border-radius: 6px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--navy); font-weight: 600; font-size: 13.5px;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $heroSlide->is_active) ? 'checked' : '' }} style="width: 17px; height: 17px;">
                    Active — Publish this slide to homepage hero slider
                </label>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 12px 28px; font-size: 14px;">
                    Save Changes &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
