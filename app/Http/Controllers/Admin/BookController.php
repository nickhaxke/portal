<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBookDocument;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\Library;
use App\Models\Tag;
use App\Services\AuditLogService;
use App\Services\SimplePdfBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Check if a duplicate publication exists by ISBN or exact Title and Author.
     */
    protected function checkDuplicate(Request $request, ?int $ignoreBookId = null): ?Book
    {
        $isbn = trim($request->input('isbn', ''));
        $isbn13 = trim($request->input('isbn13', ''));
        $title = trim($request->input('title', ''));
        $authorName = trim($request->input('author_name', ''));

        $query = Book::query();
        if ($ignoreBookId) {
            $query->where('id', '!=', $ignoreBookId);
        }

        // 1. Check ISBN match
        if (! empty($isbn) || ! empty($isbn13)) {
            $isbnDuplicate = (clone $query)->where(function ($q) use ($isbn, $isbn13) {
                if ($isbn) {
                    $q->where('isbn', $isbn)->orWhere('isbn13', $isbn);
                }
                if ($isbn13) {
                    $q->orWhere('isbn', $isbn13)->orWhere('isbn13', $isbn13);
                }
            })->first();

            if ($isbnDuplicate) {
                return $isbnDuplicate;
            }
        }

        // 2. Check exact title & author match
        if (! empty($title) && ! empty($authorName)) {
            $titleAuthorDuplicate = (clone $query)
                ->where('title', $title)
                ->where('author_name', $authorName)
                ->first();

            if ($titleAuthorDuplicate) {
                return $titleAuthorDuplicate;
            }
        }

        return null;
    }

    /**
     * Display a listing of books with advanced search, filters, sorting and trash view.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $libraryId = $request->input('library_id');
        $publicationType = $request->input('publication_type');
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'latest');

        if ($status === 'trashed') {
            $query = Book::onlyTrashed()->with(['category', 'author', 'primaryPdf', 'library', 'tags']);
        } else {
            $query = Book::with(['category', 'author', 'primaryPdf', 'categories', 'library', 'tags']);
            if ($status) {
                $query->where('status', $status);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('doi', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('series', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('library', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('tags', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                    ->orWhereHas('categories', function ($sub) use ($categoryId) {
                        $sub->where('categories.id', $categoryId);
                    });
            });
        }

        if ($libraryId) {
            $query->where('library_id', $libraryId);
        }

        if ($publicationType) {
            $query->where('publication_type', $publicationType);
        }

        match ($sortBy) {
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'year_desc' => $query->orderByDesc('publication_year'),
            'pages_desc' => $query->orderByDesc('pages_count'),
            default => $query->latest(),
        };

        $books = $query->paginate(12)->withQueryString();
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        $libraries = Library::orderBy('sort_order')->orderBy('name')->get();
        $publicationTypes = config('portal.publication_types', []);

        $counts = [
            'all' => Book::count(),
            'published' => Book::where('status', 'published')->count(),
            'draft' => Book::where('status', 'draft')->count(),
            'archived' => Book::where('status', 'archived')->count(),
            'trashed' => Book::onlyTrashed()->count(),
        ];

        return view('admin.books.index', compact(
            'books',
            'categories',
            'libraries',
            'publicationTypes',
            'search',
            'categoryId',
            'libraryId',
            'publicationType',
            'status',
            'sortBy',
            'counts'
        ));
    }

    /**
     * Show form for adding a new book.
     */
    public function create(): View
    {
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        $allCategories = Category::orderBy('name')->get();
        $libraries = Library::orderBy('sort_order')->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();
        $publicationTypes = config('portal.publication_types', []);

        $palettes = [
            ['bg' => '#102946', 'fg' => '#ffffff', 'label' => 'Executive Navy'],
            ['bg' => '#e6d9c3', 'fg' => '#9c6325', 'label' => 'Warm Cream & Gold'],
            ['bg' => '#e7e3d9', 'fg' => '#243449', 'label' => 'Classic Slate'],
            ['bg' => '#e36c34', 'fg' => '#ffffff', 'label' => 'Cognitive Rust'],
            ['bg' => '#e7eee8', 'fg' => '#17565d', 'label' => 'Forest Sage'],
            ['bg' => '#12669b', 'fg' => '#ffffff', 'label' => 'Deep Cerulean'],
            ['bg' => '#1a1a1c', 'fg' => '#ffffff', 'label' => 'Radical Obsidian'],
            ['bg' => '#2d5671', 'fg' => '#ffffff', 'label' => 'Nordic Pine'],
            ['bg' => '#755d4b', 'fg' => '#ffffff', 'label' => 'Bronze Mahogany'],
        ];

        return view('admin.books.create', compact('categories', 'allCategories', 'libraries', 'tags', 'authors', 'publicationTypes', 'palettes'));
    }

    /**
     * Store a newly created book, files and pivot relations.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $duplicate = $this->checkDuplicate($request);
        if ($duplicate && ! $request->boolean('confirm_duplicate')) {
            $warningMsg = "A publication with matching title/author or ISBN already exists: '{$duplicate->title}' by {$duplicate->author_name} (ID #{$duplicate->id}). Confirm duplicate below to proceed.";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'warning' => true,
                    'duplicate' => [
                        'id' => $duplicate->id,
                        'title' => $duplicate->title,
                        'author_name' => $duplicate->author_name,
                        'isbn' => $duplicate->isbn,
                    ],
                    'message' => $warningMsg,
                ], 422);
            }

            return back()->withInput()->with('duplicate_warning', $warningMsg)->with('duplicate_id', $duplicate->id);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'publication_type' => ['nullable', 'string', 'in:book,research_paper,report,policy_document,thesis'],
            'library_id' => ['nullable', 'exists:libraries,id'],
            'author_name' => ['required', 'string', 'max:150'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['exists:authors,id'],
            'co_author_ids' => ['nullable', 'array'],
            'co_author_ids.*' => ['exists:authors,id'],
            'editor_ids' => ['nullable', 'array'],
            'editor_ids.*' => ['exists:authors,id'],
            'publisher' => ['nullable', 'string', 'max:191'],
            'language' => ['nullable', 'string', 'max:20'],
            'edition' => ['nullable', 'string', 'max:50'],
            'series' => ['nullable', 'string', 'max:191'],
            'series_number' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'category_id' => ['required', 'exists:categories,id'],
            'secondary_category_ids' => ['nullable', 'array'],
            'secondary_category_ids.*' => ['exists:categories,id'],
            'tags' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'citation_text' => ['nullable', 'string'],
            'cover_bg' => ['nullable', 'string', 'max:30'],
            'cover_fg' => ['nullable', 'string', 'max:30'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf,epub', 'max:51200'],
            'pages_count' => ['nullable', 'integer', 'min:1'],
            'published_year' => ['nullable', 'string', 'max:10'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'isbn13' => ['nullable', 'string', 'max:30'],
            'doi' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'visibility' => ['nullable', 'string', 'in:public,restricted,private'],
            'allow_download' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'save_action' => ['nullable', 'string', 'in:draft,publish'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $author = Author::firstOrCreate(
                ['slug' => Str::slug($validated['author_name'])],
                ['name' => $validated['author_name']]
            );

            $slug = Str::slug($validated['title']);
            $count = Book::withTrashed()->where('slug', 'like', "{$slug}%")->count();
            if ($count > 0) {
                $slug .= '-'.($count + 1);
            }

            $coverImagePath = null;
            if ($request->hasFile('cover_image')) {
                $coverImagePath = $request->file('cover_image')->store('covers', 'public');
            }

            $saveAction = $request->input('save_action');
            if ($saveAction === 'draft') {
                $status = 'draft';
            } elseif ($saveAction === 'publish') {
                $status = 'published';
            } else {
                $status = $validated['status'] ?? 'published';
            }

            $yearVal = $validated['publication_year'] ?? ($validated['published_year'] ?? date('Y'));
            $yearInt = is_numeric($yearVal) ? (int) $yearVal : (int) date('Y');

            $book = Book::create([
                'library_id' => $validated['library_id'] ?? Library::first()?->id,
                'title' => $validated['title'],
                'subtitle' => $validated['subtitle'] ?? null,
                'slug' => $slug,
                'publication_type' => $validated['publication_type'] ?? 'book',
                'category_id' => $validated['category_id'],
                'author_id' => $author->id,
                'author_name' => $validated['author_name'],
                'publisher' => $validated['publisher'] ?? null,
                'language' => $validated['language'] ?? 'en',
                'edition' => $validated['edition'] ?? null,
                'series' => $validated['series'] ?? null,
                'series_number' => $validated['series_number'] ?? null,
                'publication_year' => $yearInt,
                'published_year' => (string) $yearVal,
                'description' => $validated['description'] ?? null,
                'citation_text' => $validated['citation_text'] ?? null,
                'cover_bg' => $validated['cover_bg'] ?? '#102946',
                'cover_fg' => $validated['cover_fg'] ?? '#ffffff',
                'cover_image' => $coverImagePath,
                'status' => $status,
                'visibility' => $validated['visibility'] ?? 'public',
                'allow_download' => $request->boolean('allow_download'),
                'published_at' => $status === 'published' ? now() : null,
                'is_featured' => $request->boolean('is_featured'),
                'pages_count' => $validated['pages_count'] ?? 1,
                'isbn' => $validated['isbn'] ?? null,
                'isbn13' => $validated['isbn13'] ?? null,
                'doi' => $validated['doi'] ?? null,
            ]);

            // Sync Tags
            $book->syncTags($validated['tags'] ?? null);

            // Sync Secondary Categories
            $categoriesToSync = [$validated['category_id'] => ['is_primary' => true]];
            if (! empty($validated['secondary_category_ids'])) {
                foreach ($validated['secondary_category_ids'] as $secId) {
                    if ($secId != $validated['category_id']) {
                        $categoriesToSync[$secId] = ['is_primary' => false];
                    }
                }
            }
            $book->categories()->sync($categoriesToSync);

            // Sync Multi-Authors with roles (lead_author, co_author, editor, contributor)
            $authorsToSync = [$author->id => ['role' => 'lead_author', 'sort_order' => 1]];
            $order = 2;

            if (! empty($validated['co_author_ids'])) {
                foreach ($validated['co_author_ids'] as $coId) {
                    if ($coId != $author->id && ! isset($authorsToSync[$coId])) {
                        $authorsToSync[$coId] = ['role' => 'co_author', 'sort_order' => $order++];
                    }
                }
            }

            if (! empty($validated['editor_ids'])) {
                foreach ($validated['editor_ids'] as $edId) {
                    if ($edId != $author->id && ! isset($authorsToSync[$edId])) {
                        $authorsToSync[$edId] = ['role' => 'editor', 'sort_order' => $order++];
                    }
                }
            }

            if (! empty($validated['author_ids'])) {
                foreach ($validated['author_ids'] as $otherAuthorId) {
                    if ($otherAuthorId != $author->id && ! isset($authorsToSync[$otherAuthorId])) {
                        $authorsToSync[$otherAuthorId] = ['role' => 'contributor', 'sort_order' => $order++];
                    }
                }
            }
            $book->authors()->sync($authorsToSync);

            // File handling with SHA-256 hash, versioning, and background metadata processing
            $createdBookFile = null;
            if ($request->hasFile('pdf_file')) {
                $file = $request->file('pdf_file');
                $originalName = $file->getClientOriginalName();
                $size = $file->getSize();
                $ext = strtolower($file->getClientOriginalExtension());
                $storedPath = $file->store('private/books', 'local');
                $fullPath = Storage::disk('local')->path($storedPath);
                $hash = file_exists($fullPath) ? hash_file('sha256', $fullPath) : null;

                $createdBookFile = BookFile::create([
                    'book_id' => $book->id,
                    'file_path' => $storedPath,
                    'file_name' => $originalName,
                    'file_size' => $size,
                    'file_type' => $ext,
                    'file_hash' => $hash,
                    'version' => 1,
                    'disk' => 'local',
                    'page_count' => $validated['pages_count'] ?? 1,
                    'is_active' => true,
                ]);

                // Asynchronously extract metadata, calculate deep hashes, and accurate page count
                ProcessBookDocument::dispatchAfterResponse($book->id, $createdBookFile->id);
            } else {
                $category = Category::find($validated['category_id']);
                $pdfBinary = SimplePdfBuilder::createSamplePdf(
                    $book->title,
                    $book->author_name,
                    $category?->name ?? 'Leadership',
                    $book->description ?? 'Executive summary document.'
                );

                $storedPath = 'private/books/'.$slug.'.pdf';
                Storage::disk('local')->put($storedPath, $pdfBinary);

                $createdBookFile = BookFile::create([
                    'book_id' => $book->id,
                    'file_path' => $storedPath,
                    'file_name' => $slug.'-executive-edition.pdf',
                    'file_size' => strlen($pdfBinary),
                    'file_type' => 'pdf',
                    'file_hash' => hash('sha256', $pdfBinary),
                    'version' => 1,
                    'disk' => 'local',
                    'page_count' => 3,
                    'is_active' => true,
                ]);
            }

            AuditLogService::log('book.create', $book, null, $book->toArray());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Publication '{$book->title}' saved successfully!",
                    'redirect' => route('admin.books.index'),
                    'book_id' => $book->id,
                    'slug' => $book->slug,
                    'title' => $book->title,
                ]);
            }

            return redirect()->route('admin.books.index')
                ->with('success', "Publication '{$book->title}' saved successfully!");
        });
    }

    /**
     * Show form for editing a book.
     */
    /**
     * Show form for editing a book.
     */
    public function edit(Book $book): View
    {
        $book->load(['category', 'author', 'primaryPdf', 'categories', 'authors', 'files', 'library', 'tags']);
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        $allCategories = Category::orderBy('name')->get();
        $libraries = Library::orderBy('sort_order')->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();
        $publicationTypes = config('portal.publication_types', []);

        $palettes = [
            ['bg' => '#102946', 'fg' => '#ffffff', 'label' => 'Executive Navy'],
            ['bg' => '#e6d9c3', 'fg' => '#9c6325', 'label' => 'Warm Cream & Gold'],
            ['bg' => '#e7e3d9', 'fg' => '#243449', 'label' => 'Classic Slate'],
            ['bg' => '#e36c34', 'fg' => '#ffffff', 'label' => 'Cognitive Rust'],
            ['bg' => '#e7eee8', 'fg' => '#17565d', 'label' => 'Forest Sage'],
            ['bg' => '#12669b', 'fg' => '#ffffff', 'label' => 'Deep Cerulean'],
            ['bg' => '#1a1a1c', 'fg' => '#ffffff', 'label' => 'Radical Obsidian'],
            ['bg' => '#2d5671', 'fg' => '#ffffff', 'label' => 'Nordic Pine'],
            ['bg' => '#755d4b', 'fg' => '#ffffff', 'label' => 'Bronze Mahogany'],
        ];

        $coAuthorIds = $book->authors->where('pivot.role', 'co_author')->pluck('id')->all();
        $editorIds = $book->authors->whereIn('pivot.role', ['editor', 'translator', 'contributor'])->pluck('id')->all();
        $activeFile = $book->primaryPdf ?: $book->files()->where('is_active', true)->first() ?: $book->files()->latest()->first();

        return view('admin.books.edit', compact(
            'book',
            'categories',
            'allCategories',
            'libraries',
            'tags',
            'authors',
            'publicationTypes',
            'palettes',
            'coAuthorIds',
            'editorIds',
            'activeFile'
        ));
    }

    /**
     * Update an existing book and optional replacement files.
     */
    public function update(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $duplicate = $this->checkDuplicate($request, $book->id);
        if ($duplicate && ! $request->boolean('confirm_duplicate')) {
            $warningMsg = "A publication with matching title/author or ISBN already exists: '{$duplicate->title}' by {$duplicate->author_name} (ID #{$duplicate->id}). Confirm duplicate below to proceed.";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'warning' => true,
                    'duplicate' => [
                        'id' => $duplicate->id,
                        'title' => $duplicate->title,
                        'author_name' => $duplicate->author_name,
                        'isbn' => $duplicate->isbn,
                    ],
                    'message' => $warningMsg,
                ], 422);
            }

            return back()->withInput()->with('duplicate_warning', $warningMsg)->with('duplicate_id', $duplicate->id);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'publication_type' => ['nullable', 'string', 'in:book,research_paper,report,policy_document,thesis'],
            'library_id' => ['nullable', 'exists:libraries,id'],
            'author_name' => ['required', 'string', 'max:150'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['exists:authors,id'],
            'co_author_ids' => ['nullable', 'array'],
            'co_author_ids.*' => ['exists:authors,id'],
            'editor_ids' => ['nullable', 'array'],
            'editor_ids.*' => ['exists:authors,id'],
            'publisher' => ['nullable', 'string', 'max:191'],
            'language' => ['nullable', 'string', 'max:20'],
            'edition' => ['nullable', 'string', 'max:50'],
            'series' => ['nullable', 'string', 'max:191'],
            'series_number' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'category_id' => ['required', 'exists:categories,id'],
            'secondary_category_ids' => ['nullable', 'array'],
            'secondary_category_ids.*' => ['exists:categories,id'],
            'tags' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'citation_text' => ['nullable', 'string'],
            'cover_bg' => ['nullable', 'string', 'max:30'],
            'cover_fg' => ['nullable', 'string', 'max:30'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf,epub', 'max:51200'],
            'pages_count' => ['nullable', 'integer', 'min:1'],
            'published_year' => ['nullable', 'string', 'max:10'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'isbn13' => ['nullable', 'string', 'max:30'],
            'doi' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'visibility' => ['nullable', 'string', 'in:public,restricted,private'],
            'allow_download' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'save_action' => ['nullable', 'string', 'in:draft,publish'],
        ]);

        return DB::transaction(function () use ($request, $validated, $book) {
            $oldValues = $book->toArray();

            $author = Author::firstOrCreate(
                ['slug' => Str::slug($validated['author_name'])],
                ['name' => $validated['author_name']]
            );

            // Handle cover removal
            if ($request->boolean('remove_cover') && $book->cover_image) {
                if (Storage::disk('public')->exists($book->cover_image)) {
                    Storage::disk('public')->delete($book->cover_image);
                }
                $book->cover_image = null;
            }

            if ($request->hasFile('cover_image')) {
                if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
                    Storage::disk('public')->delete($book->cover_image);
                }
                $book->cover_image = $request->file('cover_image')->store('covers', 'public');
            }

            $saveAction = $request->input('save_action');
            if ($saveAction === 'draft') {
                $newStatus = 'draft';
            } elseif ($saveAction === 'publish') {
                $newStatus = 'published';
            } else {
                $newStatus = $validated['status'] ?? $book->status;
            }

            $publishedAt = $book->published_at;
            $archivedAt = $book->archived_at;

            if ($newStatus === 'published' && ! $publishedAt) {
                $publishedAt = now();
            }
            if ($newStatus === 'archived' && ! $archivedAt) {
                $archivedAt = now();
            }

            $yearVal = $validated['publication_year'] ?? ($validated['published_year'] ?? $book->publication_year);
            $yearInt = is_numeric($yearVal) ? (int) $yearVal : $book->publication_year;

            $book->update([
                'library_id' => $validated['library_id'] ?? $book->library_id ?? Library::first()?->id,
                'title' => $validated['title'],
                'subtitle' => $validated['subtitle'] ?? null,
                'publication_type' => $validated['publication_type'] ?? $book->publication_type,
                'category_id' => $validated['category_id'],
                'author_id' => $author->id,
                'author_name' => $validated['author_name'],
                'publisher' => $validated['publisher'] ?? null,
                'language' => $validated['language'] ?? ($book->language ?? 'en'),
                'edition' => $validated['edition'] ?? null,
                'series' => $validated['series'] ?? null,
                'series_number' => $validated['series_number'] ?? null,
                'publication_year' => $yearInt,
                'published_year' => $yearVal !== null ? (string) $yearVal : $book->published_year,
                'description' => $validated['description'] ?? null,
                'citation_text' => $validated['citation_text'] ?? null,
                'cover_bg' => $validated['cover_bg'] ?? $book->cover_bg,
                'cover_fg' => $validated['cover_fg'] ?? $book->cover_fg,
                'cover_image' => $book->cover_image,
                'status' => $newStatus,
                'visibility' => $validated['visibility'] ?? $book->visibility,
                'allow_download' => $request->boolean('allow_download'),
                'published_at' => $publishedAt,
                'archived_at' => $archivedAt,
                'is_featured' => $request->boolean('is_featured'),
                'pages_count' => $validated['pages_count'] ?? $book->pages_count,
                'isbn' => $validated['isbn'] ?? $book->isbn,
                'isbn13' => $validated['isbn13'] ?? $book->isbn13,
                'doi' => $validated['doi'] ?? $book->doi,
            ]);

            // Sync Tags
            $book->syncTags($validated['tags'] ?? null);

            // Sync Secondary Categories
            $categoriesToSync = [$validated['category_id'] => ['is_primary' => true]];
            if (! empty($validated['secondary_category_ids'])) {
                foreach ($validated['secondary_category_ids'] as $secId) {
                    if ($secId != $validated['category_id']) {
                        $categoriesToSync[$secId] = ['is_primary' => false];
                    }
                }
            }
            $book->categories()->sync($categoriesToSync);

            // Sync Multi-Authors with roles (lead_author, co_author, editor, contributor)
            $authorsToSync = [$author->id => ['role' => 'lead_author', 'sort_order' => 1]];
            $order = 2;

            if (! empty($validated['co_author_ids'])) {
                foreach ($validated['co_author_ids'] as $coId) {
                    if ($coId != $author->id && ! isset($authorsToSync[$coId])) {
                        $authorsToSync[$coId] = ['role' => 'co_author', 'sort_order' => $order++];
                    }
                }
            }

            if (! empty($validated['editor_ids'])) {
                foreach ($validated['editor_ids'] as $edId) {
                    if ($edId != $author->id && ! isset($authorsToSync[$edId])) {
                        $authorsToSync[$edId] = ['role' => 'editor', 'sort_order' => $order++];
                    }
                }
            }

            if (! empty($validated['author_ids'])) {
                foreach ($validated['author_ids'] as $otherAuthorId) {
                    if ($otherAuthorId != $author->id && ! isset($authorsToSync[$otherAuthorId])) {
                        $authorsToSync[$otherAuthorId] = ['role' => 'contributor', 'sort_order' => $order++];
                    }
                }
            }
            $book->authors()->sync($authorsToSync);

            // Handle file update with version history, SHA-256 and background metadata processing
            if ($request->hasFile('pdf_file')) {
                $file = $request->file('pdf_file');
                $storedPath = $file->store('private/books', 'local');
                $fullPath = Storage::disk('local')->path($storedPath);
                $hash = file_exists($fullPath) ? hash_file('sha256', $fullPath) : null;
                $ext = strtolower($file->getClientOriginalExtension());

                $latestVersion = (int) $book->files()->max('version');
                $newVersion = $latestVersion + 1;

                // Deactivate prior files
                $book->files()->update(['is_active' => false]);

                $updatedFile = BookFile::create([
                    'book_id' => $book->id,
                    'file_path' => $storedPath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $ext,
                    'file_hash' => $hash,
                    'version' => $newVersion,
                    'disk' => 'local',
                    'page_count' => $validated['pages_count'] ?? $book->pages_count,
                    'is_active' => true,
                ]);

                // Asynchronously extract metadata and update page count
                ProcessBookDocument::dispatchAfterResponse($book->id, $updatedFile->id);
            }

            AuditLogService::log('book.update', $book, $oldValues, $book->fresh()->toArray());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Publication '{$book->title}' updated successfully!",
                    'redirect' => route('admin.books.index'),
                    'book_id' => $book->id,
                    'slug' => $book->slug,
                    'title' => $book->title,
                ]);
            }

            return redirect()->route('admin.books.index')
                ->with('success', "Publication '{$book->title}' updated successfully!");
        });
    }

    /**
     * Soft delete publication (keeps physical files for safe restore).
     */
    public function destroy(Book $book): RedirectResponse
    {
        $title = $book->title;
        $book->delete();

        AuditLogService::log('book.soft_delete', $book, ['id' => $book->id, 'title' => $title], null);

        return redirect()->route('admin.books.index')
            ->with('success', "Publication '{$title}' moved to trash. You can restore it anytime.");
    }

    /**
     * Restore soft-deleted publication.
     */
    public function restore(int $id): RedirectResponse
    {
        $book = Book::onlyTrashed()->findOrFail($id);
        $book->restore();

        AuditLogService::log('book.restore', $book, null, ['id' => $book->id, 'title' => $book->title]);

        return redirect()->route('admin.books.index', ['status' => 'trashed'])
            ->with('success', "Publication '{$book->title}' restored to library.");
    }

    /**
     * Permanently delete publication and remove files from private storage.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $book = Book::withTrashed()->findOrFail($id);

        return DB::transaction(function () use ($book) {
            $title = $book->title;
            $oldData = ['id' => $book->id, 'title' => $title];

            // Remove cover if exists
            if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
                Storage::disk('public')->delete($book->cover_image);
            }

            // Remove all associated file versions
            foreach ($book->files as $file) {
                if (Storage::disk($file->disk)->exists($file->file_path)) {
                    Storage::disk($file->disk)->delete($file->file_path);
                }
            }

            // Detach pivot tables
            $book->categories()->detach();
            $book->authors()->detach();
            $book->collections()->detach();
            $book->files()->delete();

            $book->forceDelete();

            AuditLogService::log('book.force_delete', null, $oldData, null);

            return redirect()->route('admin.books.index', ['status' => 'trashed'])
                ->with('success', "Publication '{$title}' permanently deleted from library.");
        });
    }

    /**
     * Handle bulk administrative actions on multiple selected publications.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:publish,draft,archive,soft_delete,restore'],
            'book_ids' => ['required', 'array'],
            'book_ids.*' => ['integer'],
        ]);

        $action = $validated['action'];
        $ids = $validated['book_ids'];

        $count = 0;

        match ($action) {
            'publish' => $count = Book::whereIn('id', $ids)->update(['status' => 'published', 'published_at' => now()]),
            'draft' => $count = Book::whereIn('id', $ids)->update(['status' => 'draft']),
            'archive' => $count = Book::whereIn('id', $ids)->update(['status' => 'archived', 'archived_at' => now()]),
            'soft_delete' => $count = Book::whereIn('id', $ids)->delete(),
            'restore' => $count = Book::onlyTrashed()->whereIn('id', $ids)->restore(),
        };

        AuditLogService::log('book.bulk_action', null, null, ['action' => $action, 'count' => $count, 'ids' => $ids]);

        return back()->with('success', "Bulk action '{$action}' successfully executed on {$count} publication(s).");
    }
}
