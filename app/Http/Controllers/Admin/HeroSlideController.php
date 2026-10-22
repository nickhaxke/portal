<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Services\AuditLogService;
use App\Services\EditorialImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeroSlideController extends Controller
{
    public function __construct(private EditorialImageService $images) {}

    public function index(): View
    {
        $slides = HeroSlide::ordered()->get();

        return view('admin.hero-slides.index', compact('slides'));
    }

    public function create(): View
    {
        $nextOrder = (HeroSlide::max('sort_order') ?? 0) + 1;

        return view('admin.hero-slides.create', compact('nextOrder'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSlide($request, true);

        $slide = HeroSlide::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_path' => $this->images->store($request->file('image'), 'hero'),
            'cta_text' => ($validated['cta_text'] ?? null) ?: 'Explore Collection',
            'cta_url' => ($validated['cta_url'] ?? null) ?: '/explore',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogService::log('hero_slide.create', $slide, null, $slide->toArray());

        return redirect()->route('admin.hero-slides.index')
            ->with('success', "Hero slide '{$slide->title}' created successfully.");
    }

    public function edit(HeroSlide $heroSlide): View
    {
        return view('admin.hero-slides.edit', compact('heroSlide'));
    }

    public function update(Request $request, HeroSlide $heroSlide): RedirectResponse
    {
        $validated = $this->validateSlide($request, false);
        $oldValues = $heroSlide->toArray();

        if ($request->hasFile('image')) {
            $heroSlide->image_path = $this->images->replace($heroSlide->image_path, $request->file('image'), 'hero');
        }

        $heroSlide->fill([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'cta_text' => ($validated['cta_text'] ?? null) ?: 'Explore Collection',
            'cta_url' => ($validated['cta_url'] ?? null) ?: '/explore',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        AuditLogService::log('hero_slide.update', $heroSlide, $oldValues, $heroSlide->toArray());

        return redirect()->route('admin.hero-slides.index')
            ->with('success', "Hero slide '{$heroSlide->title}' updated successfully.");
    }

    public function toggleActive(HeroSlide $heroSlide): RedirectResponse
    {
        $heroSlide->update(['is_active' => ! $heroSlide->is_active]);

        AuditLogService::log('hero_slide.toggle', $heroSlide, null, ['is_active' => $heroSlide->is_active]);

        $state = $heroSlide->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Hero slide '{$heroSlide->title}' {$state}.");
    }

    public function destroy(HeroSlide $heroSlide): RedirectResponse
    {
        $oldValues = $heroSlide->toArray();
        $title = $heroSlide->title;
        $path = $heroSlide->image_path;

        $this->images->deleteIfUnreferenced($path, 1);
        $heroSlide->delete();

        AuditLogService::log('hero_slide.delete', null, $oldValues, null);

        return redirect()->route('admin.hero-slides.index')
            ->with('success', "Hero slide '{$title}' deleted.");
    }

    private function validateSlide(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => EditorialImageService::rules($imageRequired, 1200, 600),
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:191', 'regex:'.HeroSlide::CTA_PATTERN],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'cta_url.regex' => 'The CTA destination must be an internal portal path starting with "/" (e.g. /explore, /insights, /categories/leadership).',
            'image.dimensions' => 'Hero artwork must be at least 1200×600 pixels.',
        ]);
    }
}
