<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookBookmark;
use App\Models\Category;
use App\Models\Insight;
use App\Models\Library;
use App\Models\ReadingProgress;
use App\Models\SavedBook;
use App\Services\CatalogueFilterService;
use App\Services\PublicationAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function __construct(
        public CatalogueFilterService $filterService,
        public ?PublicationAccessService $accessService = null
    ) {
        $this->accessService = $accessService ?? app(PublicationAccessService::class);
    }

    /**
     * Explore the library catalogue with search, filtering and sorting.
     */
    public function explore(Request $request): View|JsonResponse
    {
        $filters = $this->filterService->parseFilters($request);
        $user = $request->user();

        $booksQuery = $this->filterService->buildBaseQuery(null, $user)
            ->with(['category', 'author', 'library']);

        $booksQuery = $this->filterService->applyFilters($booksQuery, $filters);
        $booksQuery = $this->filterService->applySorting($booksQuery, $filters['sort']);

        $books = $booksQuery->paginate(20)->withQueryString();
        $categories = $this->filterService->getCategories();
        $libraries = $this->filterService->getAccessibleLibraries($user);
        $facetCounts = $this->filterService->calculateFacetCounts($filters, null, $user);
        $publicationTypes = config('portal.publication_types', []);

        $query = $filters['q'];
        $searchBy = $filters['search_by'];
        $categorySlug = implode(',', $filters['categories']);
        $publicationType = implode(',', $filters['types']);
        $librarySlug = implode(',', $filters['libraries']);
        $yearRange = implode(',', $filters['years']);
        $sort = $filters['sort'];
        $view = $filters['view'];

        $pubTypeCounts = $facetCounts['types'];
        $yearEraCounts = $facetCounts['years'];

        $selectedCategory = ! empty($filters['categories']) ? Category::where('slug', $filters['categories'][0])->first() : null;
        $selectedLibrary = ! empty($filters['libraries']) ? Library::where('slug', $filters['libraries'][0])->first() : null;

        if ($request->wantsJson() || $request->ajax()) {
            $viewName = $view === 'list' ? 'components.books-list' : 'components.books-grid';

            return response()->json([
                'html' => view($viewName, ['books' => $books])->render(),
                'count' => $books->total(),
                'pagination' => $books->links()->toHtml(),
                'facet_counts' => $facetCounts,
            ]);
        }

        return view('explore', compact(
            'books',
            'categories',
            'libraries',
            'publicationTypes',
            'pubTypeCounts',
            'yearEraCounts',
            'facetCounts',
            'filters',
            'query',
            'searchBy',
            'selectedCategory',
            'selectedLibrary',
            'categorySlug',
            'publicationType',
            'librarySlug',
            'yearRange',
            'sort',
            'view'
        ));
    }

    /**
     * Public browsing of distinct digital libraries.
     */
    public function libraries(): View
    {
        $libraries = Library::published()
            ->when(! auth()->check() || ! auth()->user()->isAdmin(), function ($q) {
                $q->public();
            })
            ->withCount(['books' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('libraries.index', compact('libraries'));
    }

    /**
     * Browse books within a specific library with faceted filtering, sorting, and view modes.
     */
    public function libraryShow(Request $request, string $slug): View|JsonResponse
    {
        $user = $request->user();
        $library = Library::published()
            ->when(! $user || ! $user->isAdmin(), function ($q) use ($user) {
                $q->whereIn('visibility', $user ? ['public', 'restricted'] : ['public']);
            })
            ->where('slug', $slug)
            ->firstOrFail();

        $filters = $this->filterService->parseFilters($request);
        $filters['libraries'] = [$library->slug];

        $booksQuery = $this->filterService->buildBaseQuery($library, $user)
            ->with(['category', 'author', 'library']);

        $booksQuery = $this->filterService->applyFilters($booksQuery, $filters, $library);
        $booksQuery = $this->filterService->applySorting($booksQuery, $filters['sort']);

        $books = $booksQuery->paginate(18)->withQueryString();
        $categories = $this->filterService->getCategories();
        $facetCounts = $this->filterService->calculateFacetCounts($filters, $library, $user);
        $publicationTypes = config('portal.publication_types', []);

        $query = $filters['q'];
        $searchBy = $filters['search_by'];
        $categorySlug = implode(',', $filters['categories']);
        $publicationType = implode(',', $filters['types']);
        $yearRange = implode(',', $filters['years']);
        $sort = $filters['sort'];
        $view = $filters['view'];

        $pubTypeCounts = $facetCounts['types'];
        $yearEraCounts = $facetCounts['years'];

        if ($request->wantsJson() || $request->ajax()) {
            $viewName = $view === 'list' ? 'components.books-list' : 'components.books-grid';

            return response()->json([
                'html' => view($viewName, ['books' => $books])->render(),
                'count' => $books->total(),
                'pagination' => $books->links()->toHtml(),
                'facet_counts' => $facetCounts,
            ]);
        }

        return view('libraries.show', compact(
            'library',
            'books',
            'categories',
            'publicationTypes',
            'pubTypeCounts',
            'yearEraCounts',
            'facetCounts',
            'filters',
            'query',
            'searchBy',
            'categorySlug',
            'publicationType',
            'yearRange',
            'sort',
            'view'
        ));
    }

    /**
     * Display all knowledge areas / categories.
     */
    public function categories(): View
    {
        $categories = Category::withCount(['books' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        return view('categories', compact('categories'));
    }

    /**
     * Show books within a specific category.
     */
    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $books = Book::published()
            ->with(['category', 'author'])
            ->where('category_id', $category->id)
            ->latest('published_at')
            ->paginate(15);

        return view('category-show', compact('category', 'books'));
    }

    /**
     * Display detailed bibliographic book information.
     */
    public function book(Request $request, string $slug): View
    {
        $book = Book::with([
            'category',
            'categories',
            'author',
            'authors',
            'primaryPdf',
            'files',
            'library',
            'tags',
        ])->where('slug', $slug)->firstOrFail();

        // Enforce lifecycle visibility: Draft/Archived only visible to Admin
        if (! $book->isPublished() && (! auth()->check() || ! auth()->user()->isAdmin())) {
            abort(404);
        }

        $user = auth()->user();
        if ($book->library) {
            if (! $book->library->is_published && (! $user || ! $user->isAdmin())) {
                abort(404);
            }
            if ($book->library->isPrivate() && (! $user || ! $user->isAdmin())) {
                abort(404);
            }
            if ($book->library->isRestricted() && ! $user) {
                abort(404);
            }
        }

        $sessionId = $request->session()->getId();
        $userId = auth()->id();

        $isSaved = SavedBook::where('book_id', $book->id)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })->exists();

        // Access permissions
        $canReadOnline = $this->accessService->canReadOnline($user, $book);
        $canDownload = $this->accessService->canDownload($user, $book);
        $downloadUrl = $canDownload ? $this->accessService->generateSignedStreamUrl($book) : null;

        // Reading progress (authenticated reader or session guest)
        $readingProgress = null;
        if ($user) {
            $readingProgress = $book->userProgress($user->id);
        } else {
            $sessionProgress = session("reading_progress_{$book->id}");
            if (! empty($sessionProgress['current_page'])) {
                $cur = (int) $sessionProgress['current_page'];
                $tot = (int) ($sessionProgress['total_pages'] ?? ($book->pages_count ?: 1));
                $readingProgress = (object) [
                    'current_page' => $cur,
                    'total_pages' => $tot,
                    'percent_complete' => round(($cur / max($tot, 1)) * 100, 1),
                    'is_completed' => $cur >= $tot,
                    'last_read_at' => null,
                ];
            }
        }

        // Related publications based on real metadata relationships
        $categoryIds = array_filter(array_unique(array_merge(
            [$book->category_id],
            $book->categories->pluck('id')->all()
        )));
        $authorId = $book->author_id;
        $authorName = $book->author_name;
        $series = $book->series;

        $relatedBooks = Book::published()
            ->with(['category', 'author', 'library'])
            ->where('id', '!=', $book->id)
            ->where(function ($q) use ($book, $categoryIds, $authorId, $authorName, $series) {
                $hasCondition = false;
                if (! empty($categoryIds)) {
                    $q->where(function ($cq) use ($categoryIds) {
                        $cq->whereIn('category_id', $categoryIds)
                            ->orWhereHas('categories', fn ($mcq) => $mcq->whereIn('categories.id', $categoryIds));
                    });
                    $hasCondition = true;
                }
                if (! empty($series)) {
                    if ($hasCondition) {
                        $q->orWhere('series', $series);
                    } else {
                        $q->where('series', $series);
                        $hasCondition = true;
                    }
                }
                if ($authorId || ! empty($authorName)) {
                    $authorCondition = function ($aq) use ($authorId, $authorName) {
                        if ($authorId) {
                            $aq->where('author_id', $authorId);
                        }
                        if (! empty($authorName)) {
                            $aq->orWhere('author_name', $authorName);
                        }
                    };
                    if ($hasCondition) {
                        $q->orWhere($authorCondition);
                    } else {
                        $q->where($authorCondition);
                        $hasCondition = true;
                    }
                }
                if (! $hasCondition && ! empty($book->publication_type)) {
                    $q->where('publication_type', $book->publication_type);
                }
            })
            ->where(function ($lq) use ($user) {
                $lq->whereNull('library_id')
                    ->orWhereHas('library', function ($libQ) use ($user) {
                        $libQ->where('is_published', true);
                        if (! $user || ! $user->isAdmin()) {
                            $libQ->where('visibility', '!=', 'private');
                        }
                        if (! $user) {
                            $libQ->where('visibility', '!=', 'restricted');
                        }
                    });
            })
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('book-detail', compact(
            'book',
            'isSaved',
            'relatedBooks',
            'canReadOnline',
            'canDownload',
            'downloadUrl',
            'readingProgress'
        ));
    }

    /**
     * Toggle saved status for personal library.
     */
    public function toggleSave(Request $request, Book $book): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = auth()->id();

        $existing = SavedBook::where('book_id', $book->id)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            SavedBook::create([
                'book_id' => $book->id,
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
            ]);
            $saved = true;
        }

        $totalSaved = SavedBook::where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('user_id', $userId);
            } else {
                $q->where('session_id', $sessionId);
            }
        })->count();

        return response()->json([
            'saved' => $saved,
            'message' => $saved ? 'Book added to your personal collection.' : 'Book removed from your collection.',
            'total' => $totalSaved,
        ]);
    }

    /**
     * Display user's personal library.
     */
    public function library(Request $request): View
    {
        $sessionId = $request->session()->getId();
        $userId = auth()->id();

        $savedBookIds = SavedBook::where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('user_id', $userId);
            } else {
                $q->where('session_id', $sessionId);
            }
        })->pluck('book_id');

        $books = Book::with(['category', 'author'])
            ->whereIn('id', $savedBookIds)
            ->latest()
            ->paginate(15);

        $readingProgress = collect();
        $bookmarks = collect();

        if ($userId) {
            $readingProgress = ReadingProgress::with(['book.category', 'book.author'])
                ->where('user_id', $userId)
                ->where('is_completed', false)
                ->latest('last_read_at')
                ->take(6)
                ->get();

            $bookmarks = BookBookmark::with('book')
                ->where('user_id', $userId)
                ->latest()
                ->take(10)
                ->get();
        }

        return view('library', compact('books', 'readingProgress', 'bookmarks'));
    }

    /**
     * Display insights listing.
     */
    public function insights(): View
    {
        $insights = Insight::published()->ordered()->paginate(9);

        return view('insights.index', compact('insights'));
    }

    /**
     * Display a specific insight article.
     */
    public function insight(string $slug): View
    {
        $insight = Insight::published()->where('slug', $slug)->firstOrFail();

        return view('insights.show', compact('insight'));
    }
}
