<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $disciplines = Category::with(['children' => fn ($q) => $q->withCount('books')])
            ->withCount('books')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $standaloneCategories = Category::withCount('books')
            ->whereNotNull('parent_id')
            ->whereDoesntHave('parent')
            ->get();

        return view('admin.categories.index', compact('disciplines', 'standaloneCategories'));
    }

    public function create(): View
    {
        $parents = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['name']);
        $count = Category::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?: 'strategy',
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        AuditLogService::log(
            'category.create',
            $category,
            null,
            $category->toArray()
        );

        return redirect()->route('admin.categories.index')
            ->with('success', "Discipline/Category '{$category->name}' created successfully.");
    }

    public function edit(Category $category): View
    {
        // Prevent selecting self as parent
        $parents = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id', 'different:id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Prevent circular hierarchy
        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === $category->id) {
            return back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
        }

        $oldValues = $category->toArray();

        $category->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?: $category->icon,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        AuditLogService::log(
            'category.update',
            $category,
            $oldValues,
            $category->fresh()->toArray()
        );

        return redirect()->route('admin.categories.index')
            ->with('success', "Discipline/Category '{$category->name}' updated successfully.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->count() > 0) {
            return back()->with('error', "Cannot delete category '{$category->name}' because it contains active publications. Reassign publications first.");
        }

        if ($category->children()->count() > 0) {
            return back()->with('error', "Cannot delete parent discipline '{$category->name}' because it has subcategories. Reassign or remove subcategories first.");
        }

        $oldValues = $category->toArray();
        $name = $category->name;
        $category->delete();

        AuditLogService::log(
            'category.delete',
            $category,
            $oldValues,
            null
        );

        return redirect()->route('admin.categories.index')
            ->with('success', "Discipline/Category '{$name}' removed.");
    }
}
