<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Insight;
use App\Services\AuditLogService;
use App\Services\EditorialImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InsightController extends Controller
{
    public function __construct(private EditorialImageService $images) {}

    public function index(Request $request): View
    {
        $search = $request->input('search');
        $category = $request->input('category');

        $query = Insight::orderBy('sort_order')->latest('published_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category_name', $category);
        }

        $insights = $query->paginate(12)->withQueryString();

        return view('admin.insights.index', compact('insights', 'search', 'category'));
    }

    public function create(): View
    {
        $nextOrder = (Insight::max('sort_order') ?? 0) + 1;

        return view('admin.insights.create', compact('nextOrder'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'category_name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'read_time' => ['nullable', 'string', 'max:50'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'image' => EditorialImageService::rules(false, 600, 300),
            'image_alt' => ['nullable', 'string', 'max:191'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['title']);
        $count = Insight::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $isPublished = $request->boolean('is_published');
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->images->store($request->file('image'), 'insights');
        }

        $insight = Insight::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'category_name' => $validated['category_name'],
            'icon' => $validated['icon'] ?: 'strategy',
            'read_time' => $validated['read_time'] ?: '5 min read',
            'author_name' => ($validated['author_name'] ?? null) ?: 'Executive Editorial Board',
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $imagePath,
            'image_alt' => $validated['image_alt'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
            'content' => $validated['content'],
            'published_at' => $isPublished ? now() : null,
        ]);

        AuditLogService::log('insight.create', $insight, null, $insight->toArray());

        return redirect()->route('admin.insights.index')
            ->with('success', "Executive Insight '{$insight->title}' created successfully.");
    }

    public function edit(Insight $insight): View
    {
        return view('admin.insights.edit', compact('insight'));
    }

    public function update(Request $request, Insight $insight): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'category_name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'read_time' => ['nullable', 'string', 'max:50'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'image' => EditorialImageService::rules(false, 600, 300),
            'image_alt' => ['nullable', 'string', 'max:191'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $oldValues = $insight->toArray();
        $isPublished = $request->boolean('is_published');
        $publishedAt = $insight->published_at;

        if ($isPublished && ! $publishedAt) {
            $publishedAt = now();
        } elseif (! $isPublished) {
            $publishedAt = null;
        }

        $imagePath = $insight->featured_image;

        if ($request->hasFile('image')) {
            // Store the new file first; old file is removed only if unshared.
            $imagePath = $this->images->replace($imagePath, $request->file('image'), 'insights');
        } elseif ($request->boolean('remove_image') && $imagePath) {
            $this->images->deleteIfUnreferenced($imagePath, 1);
            $imagePath = null;
        }

        $insight->update([
            'title' => $validated['title'],
            'category_name' => $validated['category_name'],
            'icon' => $validated['icon'] ?: $insight->icon,
            'read_time' => $validated['read_time'] ?: $insight->read_time,
            'author_name' => ($validated['author_name'] ?? null) ?: 'Executive Editorial Board',
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $imagePath,
            'image_alt' => $validated['image_alt'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
            'content' => $validated['content'],
            'published_at' => $publishedAt,
        ]);

        AuditLogService::log('insight.update', $insight, $oldValues, $insight->fresh()->toArray());

        return redirect()->route('admin.insights.index')
            ->with('success', "Executive Insight '{$insight->title}' updated successfully.");
    }

    public function destroy(Insight $insight): RedirectResponse
    {
        $oldValues = $insight->toArray();

        $this->images->deleteIfUnreferenced($insight->featured_image, 1);

        $title = $insight->title;
        $insight->delete();

        AuditLogService::log('insight.delete', $insight, $oldValues, null);

        return redirect()->route('admin.insights.index')
            ->with('success', "Executive Insight '{$title}' removed.");
    }
}
