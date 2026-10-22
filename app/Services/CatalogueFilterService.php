<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogueFilterService
{
    public const ALLOWED_SORTS = [
        'latest',
        'title_asc',
        'title_desc',
        'year_desc',
        'year_asc',
        'author_asc',
    ];

    public const ERA_BUCKETS = [
        '2025-2026' => [2025, 2026],
        '2020-2024' => [2020, 2024],
        '2015-2019' => [2015, 2019],
        'older' => 2015,
    ];

    public const ALLOWED_SEARCH_FIELDS = [
        'all' => 'All Fields',
        'title' => 'Title',
        'author' => 'Author / Creator',
        'subject' => 'Subject / Discipline',
        'tags' => 'Tags / Keywords',
        'isbn_doi' => 'ISBN / DOI',
        'publisher' => 'Publisher',
        'series' => 'Series',
    ];

    public const SEARCH_FIELD_ALIASES = [
        'authors' => 'author',
        'creator' => 'author',
        'creators' => 'author',
        'subject' => 'subject',
        'discipline' => 'subject',
        'disciplines' => 'subject',
        'category' => 'subject',
        'categories' => 'subject',
        'tags' => 'tags',
        'tag' => 'tags',
        'keywords' => 'tags',
        'keyword' => 'tags',
        'isbn_doi' => 'isbn_doi',
        'isbn' => 'isbn_doi',
        'doi' => 'isbn_doi',
        'identifier' => 'isbn_doi',
        'identifiers' => 'isbn_doi',
        'publisher' => 'publisher',
        'publishers' => 'publisher',
        'series' => 'series',
    ];

    /**
     * Parse and sanitize incoming query parameters into a normalized filter array.
     *
     * @return array{
     *     q: string,
     *     search_by: string,
     *     libraries: array<string>,
     *     categories: array<string>,
     *     types: array<string>,
     *     years: array<string>,
     *     sort: string,
     *     view: string,
     *     page: int
     * }
     */
    public function parseFilters(Request $request): array
    {
        $rawQ = $request->input('q', '');
        $q = is_string($rawQ) ? trim((string) preg_replace('/\s+/', ' ', $rawQ)) : '';

        $rawSearchBy = strtolower(trim((string) $request->input('search_by', 'all')));
        $canonicalSearchBy = self::SEARCH_FIELD_ALIASES[$rawSearchBy] ?? $rawSearchBy;
        if (! array_key_exists($canonicalSearchBy, self::ALLOWED_SEARCH_FIELDS)) {
            $canonicalSearchBy = 'all';
        }

        $libraries = $this->normalizeArrayParam($request->input('library', []));
        $categories = $this->normalizeArrayParam($request->input('category', []));
        $types = $this->normalizeArrayParam($request->input('type', []));
        $years = $this->normalizeArrayParam($request->input('year', []));

        $sort = (string) $request->input('sort', 'latest');
        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'latest';
        }

        $view = (string) $request->input('view', 'grid');
        if (! in_array($view, ['grid', 'list'], true)) {
            $view = 'grid';
        }

        $page = max(1, (int) $request->input('page', 1));

        return [
            'q' => $q,
            'search_by' => $canonicalSearchBy,
            'libraries' => $libraries,
            'categories' => $categories,
            'types' => $types,
            'years' => $years,
            'sort' => $sort,
            'view' => $view,
            'page' => $page,
        ];
    }

    /**
     * Build base query enforcing published status and user-specific visibility boundaries.
     */
    public function buildBaseQuery(?Library $scopedLibrary = null, ?User $user = null): Builder
    {
        $query = Book::query()->published();

        if ($scopedLibrary !== null) {
            $query->where('library_id', $scopedLibrary->id);
        } else {
            // Apply library visibility rules
            if ($user && $user->isAdmin()) {
                // Admin can access all libraries
            } elseif ($user !== null) {
                // Authenticated readers can see books in published public/restricted libraries or without library
                $query->where(function (Builder $sub) {
                    $sub->whereNull('library_id')
                        ->orWhereHas('library', function (Builder $libQ) {
                            $libQ->where('is_published', true)
                                ->whereIn('visibility', ['public', 'restricted']);
                        });
                });
            } else {
                // Public visitors can only access books in published public libraries or without library
                $query->where(function (Builder $sub) {
                    $sub->whereNull('library_id')
                        ->orWhereHas('library', function (Builder $libQ) {
                            $libQ->where('is_published', true)
                                ->where('visibility', 'public');
                        });
                });
            }
        }

        // Apply book-level visibility rules
        if ($user && $user->isAdmin()) {
            // Admin can see any book visibility
        } elseif ($user !== null) {
            // Authenticated readers can see public and restricted/readers_only books
            $query->where(function (Builder $sub) {
                $sub->whereNull('visibility')
                    ->orWhereIn('visibility', ['public', 'restricted', 'readers_only']);
            });
        } else {
            // Public visitors can only see public books
            $query->where(function (Builder $sub) {
                $sub->whereNull('visibility')
                    ->orWhere('visibility', 'public');
            });
        }

        return $query;
    }

    /**
     * Apply active filters (keyword search, facets) with optional exclusion of facets.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $excludeFacets
     */
    public function applyFilters(Builder $query, array $filters, ?Library $scopedLibrary = null, array $excludeFacets = []): Builder
    {
        // 1. Keyword search (field-specific or all fields)
        if (! in_array('q', $excludeFacets, true) && ! empty($filters['q'])) {
            $term = $filters['q'];
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $likeTerm = '%'.$escaped.'%';

            $rawSearchBy = strtolower(trim((string) ($filters['search_by'] ?? 'all')));
            $searchBy = self::SEARCH_FIELD_ALIASES[$rawSearchBy] ?? $rawSearchBy;
            if (! array_key_exists($searchBy, self::ALLOWED_SEARCH_FIELDS)) {
                $searchBy = 'all';
            }

            $digitsOnly = preg_replace('/[^0-9Xx]/', '', $term);
            $cleanDoi = preg_replace('#^(https?://(dx\.)?doi\.org/|doi:)#i', '', $term);
            $doiLike = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cleanDoi).'%';

            match ($searchBy) {
                'title' => $query->where(function (Builder $q) use ($likeTerm) {
                    $q->where('title', 'like', $likeTerm)
                        ->orWhere('subtitle', 'like', $likeTerm);
                }),

                'author' => $query->where(function (Builder $q) use ($likeTerm) {
                    $q->where('author_name', 'like', $likeTerm)
                        ->orWhereHas('author', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm);
                        })
                        ->orWhereHas('authors', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm);
                        });
                }),

                'subject' => $query->where(function (Builder $q) use ($likeTerm) {
                    $q->whereHas('category', function (Builder $sub) use ($likeTerm) {
                        $sub->where('name', 'like', $likeTerm)
                            ->orWhere('description', 'like', $likeTerm);
                    })
                        ->orWhereHas('categories', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm)
                                ->orWhere('description', 'like', $likeTerm);
                        });
                }),

                'tags' => $query->whereHas('tags', function (Builder $sub) use ($likeTerm) {
                    $sub->where('name', 'like', $likeTerm);
                }),

                'isbn_doi' => $query->where(function (Builder $q) use ($likeTerm, $digitsOnly, $doiLike) {
                    $q->where('isbn', 'like', $likeTerm)
                        ->orWhere('isbn13', 'like', $likeTerm)
                        ->orWhere('doi', 'like', $likeTerm)
                        ->orWhere('doi', 'like', $doiLike);

                    if (strlen($digitsOnly) >= 3) {
                        $digitsLike = '%'.$digitsOnly.'%';
                        $q->orWhereRaw("REPLACE(REPLACE(isbn, '-', ''), ' ', '') LIKE ?", [$digitsLike])
                            ->orWhereRaw("REPLACE(REPLACE(isbn13, '-', ''), ' ', '') LIKE ?", [$digitsLike]);
                    }
                }),

                'publisher' => $query->where('publisher', 'like', $likeTerm),

                'series' => $query->where('series', 'like', $likeTerm),

                default => $query->where(function (Builder $q) use ($likeTerm, $digitsOnly, $doiLike) {
                    $q->where('title', 'like', $likeTerm)
                        ->orWhere('subtitle', 'like', $likeTerm)
                        ->orWhere('author_name', 'like', $likeTerm)
                        ->orWhere('description', 'like', $likeTerm)
                        ->orWhere('isbn', 'like', $likeTerm)
                        ->orWhere('isbn13', 'like', $likeTerm)
                        ->orWhere('doi', 'like', $likeTerm)
                        ->orWhere('doi', 'like', $doiLike)
                        ->orWhere('publisher', 'like', $likeTerm)
                        ->orWhere('series', 'like', $likeTerm)
                        ->orWhereHas('category', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm)
                                ->orWhere('description', 'like', $likeTerm);
                        })
                        ->orWhereHas('categories', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm)
                                ->orWhere('description', 'like', $likeTerm);
                        })
                        ->orWhereHas('author', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm);
                        })
                        ->orWhereHas('authors', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm);
                        })
                        ->orWhereHas('tags', function (Builder $sub) use ($likeTerm) {
                            $sub->where('name', 'like', $likeTerm);
                        });

                    if (strlen($digitsOnly) >= 3) {
                        $digitsLike = '%'.$digitsOnly.'%';
                        $q->orWhereRaw("REPLACE(REPLACE(isbn, '-', ''), ' ', '') LIKE ?", [$digitsLike])
                            ->orWhereRaw("REPLACE(REPLACE(isbn13, '-', ''), ' ', '') LIKE ?", [$digitsLike]);
                    }
                }),
            };
        }

        // 2. Digital Libraries facet (only if not pre-scoped to a specific library)
        if (! in_array('libraries', $excludeFacets, true) && ! empty($filters['libraries']) && $scopedLibrary === null) {
            $query->whereHas('library', function (Builder $q) use ($filters) {
                $q->whereIn('slug', $filters['libraries']);
            });
        }

        // 3. Disciplines / Categories facet
        if (! in_array('categories', $excludeFacets, true) && ! empty($filters['categories'])) {
            $query->whereHas('category', function (Builder $q) use ($filters) {
                $q->whereIn('slug', $filters['categories']);
            });
        }

        // 4. Publication Types facet
        if (! in_array('types', $excludeFacets, true) && ! empty($filters['types'])) {
            $query->whereIn('publication_type', $filters['types']);
        }

        // 5. Publication Era / Year facet
        if (! in_array('years', $excludeFacets, true) && ! empty($filters['years'])) {
            $query->where(function (Builder $yearQ) use ($filters) {
                $first = true;
                foreach ($filters['years'] as $yearSpec) {
                    $method = $first ? 'where' : 'orWhere';
                    $betweenMethod = $first ? 'whereBetween' : 'orWhereBetween';

                    if (str_contains($yearSpec, '-')) {
                        [$startY, $endY] = explode('-', $yearSpec, 2);
                        if (is_numeric($startY) && is_numeric($endY)) {
                            $yearQ->$betweenMethod('publication_year', [(int) $startY, (int) $endY]);
                            $first = false;
                        }
                    } elseif ($yearSpec === 'older') {
                        $yearQ->$method(function (Builder $sub) {
                            $sub->where('publication_year', '<', 2015)->whereNotNull('publication_year');
                        });
                        $first = false;
                    } elseif (is_numeric($yearSpec)) {
                        $yearQ->$method('publication_year', (int) $yearSpec);
                        $first = false;
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Apply requested sorting order to the query.
     */
    public function applySorting(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'year_desc' => $query->orderByRaw('publication_year IS NULL, publication_year DESC')->latest('published_at'),
            'year_asc' => $query->orderByRaw('publication_year IS NULL, publication_year ASC')->latest('published_at'),
            'author_asc' => $query->orderBy('author_name', 'asc'),
            default => $query->latest('published_at'),
        };
    }

    /**
     * Calculate context-aware facet counts using standard faceted search semantics.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     total: int,
     *     libraries: array<int, int>,
     *     libraries_total: int,
     *     categories: array<int, int>,
     *     categories_total: int,
     *     types: array<string, int>,
     *     types_total: int,
     *     years: array<string, int>,
     *     years_total: int
     * }
     */
    public function calculateFacetCounts(array $filters, ?Library $scopedLibrary = null, ?User $user = null): array
    {
        // 1. Total matching results with ALL filters applied
        $totalMatches = (clone $this->applyFilters(
            $this->buildBaseQuery($scopedLibrary, $user),
            $filters,
            $scopedLibrary
        ))->count();

        // 2. Publication Types counts (respects active search, library, category, year filters)
        $typeQuery = clone $this->applyFilters(
            $this->buildBaseQuery($scopedLibrary, $user),
            $filters,
            $scopedLibrary,
            ['types']
        );
        $typeCounts = (clone $typeQuery)
            ->selectRaw('publication_type, count(*) as count')
            ->groupBy('publication_type')
            ->pluck('count', 'publication_type')
            ->map(fn ($c) => (int) $c)
            ->toArray();
        $typesTotal = (clone $typeQuery)->count();

        // 3. Categories / Disciplines counts (respects active search, library, type, year filters)
        $catQuery = clone $this->applyFilters(
            $this->buildBaseQuery($scopedLibrary, $user),
            $filters,
            $scopedLibrary,
            ['categories']
        );
        $catCounts = (clone $catQuery)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->pluck('count', 'category_id')
            ->map(fn ($c) => (int) $c)
            ->toArray();
        $categoriesTotal = (clone $catQuery)->count();

        // 4. Digital Libraries counts (if not pre-scoped to single library)
        $libCounts = [];
        $libsTotal = 0;
        if ($scopedLibrary === null) {
            $libQuery = clone $this->applyFilters(
                $this->buildBaseQuery(null, $user),
                $filters,
                null,
                ['libraries']
            );
            $libCounts = (clone $libQuery)
                ->whereNotNull('library_id')
                ->selectRaw('library_id, count(*) as count')
                ->groupBy('library_id')
                ->pluck('count', 'library_id')
                ->map(fn ($c) => (int) $c)
                ->toArray();
            $libsTotal = (clone $libQuery)->count();
        }

        // 5. Publication Era counts (respects search, library, category, type filters) in a single aggregation query
        $yearQuery = clone $this->applyFilters(
            $this->buildBaseQuery($scopedLibrary, $user),
            $filters,
            $scopedLibrary,
            ['years']
        );

        $eraCountsRow = (clone $yearQuery)
            ->selectRaw('
                COUNT(CASE WHEN publication_year BETWEEN 2025 AND 2026 THEN 1 END) as era_recent,
                COUNT(CASE WHEN publication_year BETWEEN 2020 AND 2024 THEN 1 END) as era_2020_2024,
                COUNT(CASE WHEN publication_year BETWEEN 2015 AND 2019 THEN 1 END) as era_2015_2019,
                COUNT(CASE WHEN publication_year < 2015 AND publication_year IS NOT NULL THEN 1 END) as era_older,
                COUNT(*) as era_total
            ')
            ->first();

        $yearCounts = [
            '2025-2026' => (int) ($eraCountsRow->era_recent ?? 0),
            '2020-2024' => (int) ($eraCountsRow->era_2020_2024 ?? 0),
            '2015-2019' => (int) ($eraCountsRow->era_2015_2019 ?? 0),
            'older' => (int) ($eraCountsRow->era_older ?? 0),
        ];
        $yearsTotal = (int) ($eraCountsRow->era_total ?? 0);

        return [
            'total' => $totalMatches,
            'libraries' => $libCounts,
            'libraries_total' => $libsTotal,
            'categories' => $catCounts,
            'categories_total' => $categoriesTotal,
            'types' => $typeCounts,
            'types_total' => $typesTotal,
            'years' => $yearCounts,
            'years_total' => $yearsTotal,
        ];
    }

    /**
     * Retrieve libraries accessible to the current user.
     */
    public function getAccessibleLibraries(?User $user = null): Collection
    {
        return Library::query()
            ->published()
            ->when(! $user || ! $user->isAdmin(), function (Builder $q) use ($user) {
                if ($user !== null) {
                    $q->whereIn('visibility', ['public', 'restricted']);
                } else {
                    $q->where('visibility', 'public');
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Retrieve categories.
     */
    public function getCategories(): Collection
    {
        return Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Normalize string, comma-separated string, or array parameter into a clean string array.
     *
     * @return array<string>
     */
    protected function normalizeArrayParam(mixed $param): array
    {
        if (empty($param)) {
            return [];
        }

        if (is_string($param)) {
            $items = explode(',', $param);
        } elseif (is_array($param)) {
            $items = [];
            foreach ($param as $item) {
                if (is_string($item)) {
                    foreach (explode(',', $item) as $part) {
                        $items[] = $part;
                    }
                }
            }
        } else {
            return [];
        }

        $cleaned = [];
        foreach ($items as $item) {
            $trimmed = trim((string) $item);
            if ($trimmed !== '' && ! in_array($trimmed, $cleaned, true)) {
                $cleaned[] = $trimmed;
            }
        }

        return $cleaned;
    }
}
