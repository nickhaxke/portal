@extends('layouts.admin')

@section('title', 'Add Hero Slide')
@section('header_title', 'Create Executive Hero Slide')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.hero-slides.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Hero Slides</a>
</div>

<div class="admin-card" style="max-width: 820px;" x-data="{
    title: @js(old('title', '')),
    subtitle: @js(old('subtitle', '')),
    ctaText: @js(old('cta_text', 'Explore Collection')),
    ctaUrl: @js(old('cta_url', '/explore')),
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
    <h3 style="font: 700 20px 'Playfair Display', serif; margin: 0 0 20px; color: var(--navy);">Slide Metadata & Visual Artwork</h3>

    <form action="{{ route('admin.hero-slides.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                    value="{{ old('title') }}" 
                    class="field" 
                    required 
                    placeholder="e.g. Strategic Foresight in Complex Governance"
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
                    placeholder="Curated intelligence and peer-reviewed monographs for institutional decision makers..."
                    style="width: 100%; line-height: 1.5;"
                >{{ old('subtitle') }}</textarea>
            </div>

            <!-- Image Upload & Live Preview -->
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Hero Artwork (Landscape 16:9 or 16:10) <span style="color:#c53030;">*</span>
                </label>
                <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; background: #fafaf9; text-align: center;">
                    <template x-if="previewUrl">
                        <div style="margin-bottom: 14px; max-width: 480px; margin-left: auto; margin-right: auto;">
                            <img :src="previewUrl" alt="Preview" style="width: 100%; height: 240px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        </div>
                    </template>
                    <input 
                        type="file" 
                        id="image" 
                        name="image" 
                        accept="image/jpeg,image/png,image/webp" 
                        required 
                        @change="handleFile($event)"
                        style="font-size: 13px;"
                    >
                    <small style="display: block; color: var(--muted); margin-top: 6px; font-size: 11.5px;">
                        Recommended resolution: 1920×1080 or 1600×1000. JPEG, PNG, or WebP up to 5MB.
                    </small>
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
                        value="{{ old('cta_text', 'Explore Collection') }}" 
                        class="field" 
                        placeholder="e.g. Explore Collection"
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
                        value="{{ old('cta_url', '/explore') }}" 
                        class="field" 
                        placeholder="e.g. /explore, /categories, /insights"
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
                        value="{{ old('sort_order', $nextOrder) }}" 
                        min="0"
                        class="field" 
                        style="width: 100%;"
                    >
                </div>
            </div>

            <!-- Active Toggle -->
            <div style="padding: 12px 16px; background: #fffdf5; border: 1px solid #fae8b8; border-radius: 6px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--navy); font-weight: 600; font-size: 13.5px;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: 17px; height: 17px;">
                    Active — Publish this slide immediately to the homepage hero slider
                </label>
            </div>

            <!-- Live Slider Card Mock Preview -->
            <div style="border-top: 1px solid var(--border); padding-top: 18px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">
                    Live Card Composition Preview
                </span>
                <div style="background: #102946; border-radius: 10px; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; color: var(--gold); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px;">CURATED EDITORIAL SLIDE</div>
                        <h4 style="font: 700 17px 'Playfair Display', serif; margin: 0 0 6px; color: white;" x-text="title || 'Slide Title Preview'"></h4>
                        <p style="font-size: 12px; color: #cbd5e1; margin: 0 0 10px; max-width: 440px;" x-text="subtitle || 'Caption preview will appear here...'"></p>
                        <span class="gold" style="font-size: 11.5px; padding: 5px 12px; display: inline-block;" x-text="`${ctaText || 'CTA'} &rarr;`"></span>
                    </div>
                    <div style="width: 160px; height: 100px; border-radius: 6px; overflow: hidden; background: #1e3a5f; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.2);">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" style="width: 100%; height: 100%; object-fit: cover;">
                        </template>
                        <template x-if="!previewUrl">
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #94a3b8;">
                                Artwork Preview
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 12px 28px; font-size: 14px;">
                    Save & Publish Hero Slide &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
