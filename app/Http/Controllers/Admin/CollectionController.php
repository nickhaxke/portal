<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Collection;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        $collections = Collection::withCount('books')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(12);

        return view('admin.collections.index', compact('collections'));
    }

    public function create(): View
    {
        $books = Book::where('status', 'published')->orderBy('title')->get();

        return view('admin.collections.create', compact('books'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'book_ids' => ['nullable', 'array'],
            'book_ids.*' => ['exists:books,id'],
        ]);

        $slug = Str::slug($validated['name']);
        $count = Collection::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $collection = Collection::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (! empty($validated['book_ids'])) {
            $syncData = [];
            foreach ($validated['book_ids'] as $index => $bookId) {
                $syncData[$bookId] = ['sort_order' => $index + 1];
            }
            $collection->books()->sync($syncData);
        }

        AuditLogService::log('collection.create', $collection, null, $collection->toArray());

        return redirect()->route('admin.collections.index')
            ->with('success', "Curated Collection '{$collection->name}' created successfully.");
    }

    public function edit(Collection $collection): View
    {
        $collection->load('books');
        $books = Book::where('status', 'published')->orderBy('title')->get();
        $selectedBookIds = $collection->books->pluck('id')->toArray();

        return view('admin.collections.edit', compact('collection', 'books', 'selectedBookIds'));
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'book_ids' => ['nullable', 'array'],
            'book_ids.*' => ['exists:books,id'],
        ]);

        $oldValues = $collection->toArray();

        $collection->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (isset($validated['book_ids'])) {
            $syncData = [];
            foreach ($validated['book_ids'] as $index => $bookId) {
                $syncData[$bookId] = ['sort_order' => $index + 1];
            }
            $collection->books()->sync($syncData);
        } else {
            $collection->books()->detach();
        }

        AuditLogService::log('collection.update', $collection, $oldValues, $collection->fresh()->toArray());

        return redirect()->route('admin.collections.index')
            ->with('success', "Curated Collection '{$collection->name}' updated successfully.");
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $oldValues = $collection->toArray();
        $name = $collection->name;
        $collection->books()->detach();
        $collection->delete();

        AuditLogService::log('collection.delete', $collection, $oldValues, null);

        return redirect()->route('admin.collections.index')
            ->with('success', "Curated Collection '{$name}' removed.");
    }
}
