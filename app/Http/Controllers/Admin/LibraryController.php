<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LibraryController extends Controller
{
    /**
     * Display a listing of libraries with book counts and metrics.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $visibility = $request->input('visibility');

        $query = Library::withCount(['books', 'publishedBooks'])->orderBy('sort_order')->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($visibility) {
            $query->where('visibility', $visibility);
        }

        $libraries = $query->paginate(12)->withQueryString();

        $stats = [
            'total_libraries' => Library::count(),
            'published_libraries' => Library::where('is_published', true)->count(),
            'total_books_assigned' => Book::whereNotNull('library_id')->count(),
            'unassigned_books' => Book::whereNull('library_id')->count(),
        ];

        return view('admin.libraries.index', compact('libraries', 'search', 'visibility', 'stats'));
    }

    /**
     * Show the form for creating a new library.
     */
    public function create(): View
    {
        return view('admin.libraries.create');
    }

    /**
     * Store a newly created library.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
            'visibility' => ['required', 'string', 'in:public,restricted,private'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['name']);
        $count = Library::withTrashed()->where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('libraries', 'public');
        }

        $library = Library::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?: 'library',
            'cover_image' => $coverImagePath,
            'is_published' => $request->boolean('is_published', true),
            'visibility' => $validated['visibility'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        AuditLogService::log(
            'library.create',
            $library,
            null,
            $library->toArray()
        );

        return redirect()->route('admin.libraries.index')
            ->with('success', "Library '{$library->name}' created successfully.");
    }

    /**
     * Show library details and allow searching/filtering books within this library.
     */
    public function show(Request $request, Library $library): View
    {
        $library->loadCount(['books', 'publishedBooks']);

        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $status = $request->input('status');

        $booksQuery = $library->books()->with(['category', 'author', 'primaryPdf']);

        if ($search) {
            $booksQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $booksQuery->where('category_id', $categoryId);
        }

        if ($status) {
            $booksQuery->where('status', $status);
        }

        $books = $booksQuery->latest()->paginate(12)->withQueryString();
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();

        // Books eligible for assignment (not yet in this library)
        $availableBooks = Book::where('library_id', '!=', $library->id)
            ->orWhereNull('library_id')
            ->orderBy('title')
            ->limit(50)
            ->get();

        return view('admin.libraries.show', compact('library', 'books', 'categories', 'availableBooks', 'search', 'categoryId', 'status'));
    }

    /**
     * Show the form for editing the specified library.
     */
    public function edit(Library $library): View
    {
        return view('admin.libraries.edit', compact('library'));
    }

    /**
     * Update the specified library.
     */
    public function update(Request $request, Library $library): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
            'visibility' => ['required', 'string', 'in:public,restricted,private'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $coverImagePath = $library->cover_image;
        if ($request->hasFile('cover_image')) {
            if ($library->cover_image && Storage::disk('public')->exists($library->cover_image)) {
                Storage::disk('public')->delete($library->cover_image);
            }
            $coverImagePath = $request->file('cover_image')->store('libraries', 'public');
        }

        $oldValues = $library->toArray();

        $library->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?: $library->icon,
            'cover_image' => $coverImagePath,
            'is_published' => $request->boolean('is_published'),
            'visibility' => $validated['visibility'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        AuditLogService::log(
            'library.update',
            $library,
            $oldValues,
            $library->toArray()
        );

        return redirect()->route('admin.libraries.index')
            ->with('success', "Library '{$library->name}' updated successfully.");
    }

    /**
     * Remove the specified library.
     */
    public function destroy(Library $library): RedirectResponse
    {
        if (Library::count() <= 1) {
            return back()->with('error', 'Cannot delete the only remaining library in the system.');
        }

        // Reassign books to another default library if any
        $fallbackLibrary = Library::where('id', '!=', $library->id)->orderBy('id')->first();
        if ($fallbackLibrary) {
            Book::where('library_id', $library->id)->update(['library_id' => $fallbackLibrary->id]);
        }

        $oldValues = $library->toArray();
        $name = $library->name;
        $library->delete();

        AuditLogService::log(
            'library.delete',
            $library,
            $oldValues,
            null
        );

        return redirect()->route('admin.libraries.index')
            ->with('success', "Library '{$name}' removed. Any associated books have been moved to '{$fallbackLibrary?->name}'.");
    }

    /**
     * Bulk assign books to this library.
     */
    public function assignBooks(Request $request, Library $library): RedirectResponse
    {
        $validated = $request->validate([
            'book_ids' => ['required', 'array'],
            'book_ids.*' => ['exists:books,id'],
        ]);

        Book::whereIn('id', $validated['book_ids'])->update(['library_id' => $library->id]);

        AuditLogService::log(
            'library.assign_books',
            $library,
            null,
            ['assigned_count' => count($validated['book_ids']), 'library_id' => $library->id]
        );

        return back()->with('success', count($validated['book_ids'])." publications assigned to {$library->name}.");
    }

    /**
     * Unassign a book from this library.
     */
    public function removeBook(Library $library, Book $book): RedirectResponse
    {
        if ($book->library_id === $library->id) {
            $fallbackLibrary = Library::where('id', '!=', $library->id)->orderBy('id')->first();
            $book->update(['library_id' => $fallbackLibrary?->id]);
        }

        return back()->with('success', "Publication '{$book->title}' unassigned from {$library->name}.");
    }
}
