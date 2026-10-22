# Executive Portal — Catalogue Filtering, Search & UX Stabilization QA Report

**Project:** Executive Intelligence & Leadership E-Library (`portal`)  
**Scope:** Global Catalogue (`/explore`) & Individual Digital Libraries (`/libraries/{slug}`)  
**Date:** October 2026  
**Status:** **STABILIZED & VERIFIED** (100% Tests Passing, Real Browser Verified)

---

## Executive Summary

An audit and stabilization of the faceted catalogue experience was conducted across both the Global Catalogue (`/explore`) and Individual Digital Libraries (`/libraries/{slug}`). The system has been stabilized with a single query abstraction service ([CatalogueFilterService.php](file:///c:/wamp64/www/portal/app/Services/CatalogueFilterService.php)), multi-select facet support, context-aware dynamic facet counts, strict access-control boundaries, debounced search with stale-request protection, browser back/forward (`popstate`) restoration, collapsible facet accordions with accessible checkboxes, and a responsive mobile filter drawer.

The test suite was expanded from 9 baseline tests to **17 comprehensive feature tests (117 assertions)** in [StandardFacetedCatalogueTest.php](file:///c:/wamp64/www/portal/tests/Feature/StandardFacetedCatalogueTest.php), and all **80 portal feature tests (504 assertions)** pass without errors or regressions. Real browser automated visual verification was executed using Google Chrome, capturing desktop, search, multi-faceted, bibliographic list, reset, and mobile drawer states.

---

## 1. Root Causes of Discovered Issues

During the audit phase, several root causes were identified across query logic, security boundaries, client-side state handling, and template markup:

1. **Unfiltered & Redundant Facet Count Queries (N+1 in Templates)**:
   - Facet counts for publication types, eras, categories, and libraries were calculated against all `Book::published()` records regardless of active search queries or other selected facets.
   - The Blade template was executing inline database queries directly inside loop headers (`\App\Models\Book::published()->count()`), causing redundant database trips on every render.
   - 4 separate sequential count queries were executed for historical eras instead of a single grouped/conditional query.

2. **Divergent & Duplicated Controller Filtering Logic**:
   - `CatalogueController::explore()` and `CatalogueController::libraryShow()` maintained duplicated filtering code.
   - Search in `libraryShow()` omitted key metadata fields (`doi`, `category`, `author`, `tags`) that were present in `explore()`.

3. **Absence of Multi-Select Faceting**:
   - The controller only accepted single strings (`$request->input('category', '')`), preventing users from selecting multiple disciplines (e.g., both Strategic Leadership and Digital Transformation) or multiple publication types (e.g., Books and Research Papers).
   - Array and comma-separated inputs were not normalized and could trigger `TypeError` in PHP 8.4 (e.g., passing an array into `str_contains($yearRange, '-')`).

4. **Broken Browser Back/Forward (`popstate`) History**:
   - All filter actions called `window.history.replaceState` instead of pushing state on user intent.
   - No `popstate` event listener was registered on `window`. When users clicked browser Back or Forward buttons, the URL changed but the Alpine.js component and displayed results failed to update.

5. **Access Control & Metadata Leakage Vulnerabilities**:
   - In `explore()`, `Book::published()` did not check whether a book's associated digital library was published or accessible. Books belonging to private or unpublished libraries were exposed to unauthenticated public visitors.
   - Facet counts leaked the existence and tally of inaccessible and draft publications.
   - `CatalogueController::book()` lacked library visibility verification, allowing direct URL access to books in private libraries by unauthenticated users.

6. **Scope-Leaking Reset in Library View**:
   - In `books-grid.blade.php`, zero-result empty states displayed a link pointing to `route('explore')`. When a user encountered 0 results within an individual library (e.g. `Executive Core Library`), clicking reset unexpectedly navigated away to the global catalogue instead of clearing filters within the current library.

7. **HTML Parsing Failure in Inline Alpine `x-data`**:
   - The complex Alpine object placed directly into the `x-data` HTML attribute contained unescaped `>` comparison operators (e.g. `if (index > -1)`), which caused browser HTML parsers to prematurely close the `<section>` tag and dump raw JavaScript onto the page.

8. **Mobile Filter Trigger Text Wrapping**:
   - On narrow mobile viewports (< 390px), the "Filters" button lacked `white-space: nowrap` and `flex-shrink: 0`, causing the letter "s" to wrap awkwardly to a second line.

---

## 2. Implemented Fixes

### A. Architectural Abstraction: `CatalogueFilterService`
Created [app/Services/CatalogueFilterService.php](file:///c:/wamp64/www/portal/app/Services/CatalogueFilterService.php) to unify all query logic:
- **`parseFilters(Request $request)`**: Normalizes query parameters from string, comma-separated, or array formats (`category[]=a&category[]=b` or `category=a,b`) into sanitized string arrays. Validates `sort` against whitelist (`latest`, `title_asc`, `title_desc`, `year_desc`, `year_asc`, `author_asc`) and `view` (`grid`, `list`).
- **`buildBaseQuery(?Library $scopedLibrary, ?User $user)`**: Enforces `published` status and applies role-based visibility:
  - **Public Visitors**: Only see books in published libraries with `visibility = 'public'` (or books without library) having `visibility = 'public'`.
  - **Authenticated Readers**: Can see books in published libraries with `visibility IN ('public', 'restricted')`.
  - **Administrators**: Have full access across all published libraries.
- **`applyFilters(Builder $query, array $filters, ?Library $scopedLibrary, array $excludeFacets)`**: Applies keyword search across 11 fields (`title`, `subtitle`, `author_name`, `description`, `isbn`, `isbn13`, `doi`, `publisher`, `series`, `category.name`, `author.name`, `tags.name`) with safe LIKE escaping. Applies multi-selection OR logic within facets, and AND logic across facets.
- **`calculateFacetCounts(array $filters, ?Library $scopedLibrary, ?User $user)`**: Computes context-aware counts. For any given facet, other active filters are applied while excluding that facet itself, allowing users to see accurate result counts for potential selections. Historical era counts are evaluated in a single SQL query via conditional aggregation (`COUNT(CASE WHEN ...)`).

### B. Controller Refactoring
Updated [app/Http/Controllers/CatalogueController.php](file:///c:/wamp64/www/portal/app/Http/Controllers/CatalogueController.php):
- Injected `CatalogueFilterService` via constructor promotion.
- Streamlined `explore()` and `libraryShow()` to use the shared service, returning `facet_counts` in AJAX responses for dynamic frontend updates.
- Added library access verification to `book()` ensuring 404 responses when unauthenticated visitors attempt to access publications in private or restricted libraries.

### C. Frontend Alpine.js Component: `catalogueFaceted`
Extracted component logic from inline Blade attributes into [resources/js/app.js](file:///c:/wamp64/www/portal/resources/js/app.js) via `Alpine.data('catalogueFaceted', ...)`:
- **Popstate Navigation**: Listens to `window.popstate`, parses URL query parameters, rehydrates state, and updates books without triggering duplicate history pushes.
- **Stale-Request Protection**: Uses monotonic `activeRequestId` counter; late-arriving responses from previous requests are automatically discarded.
- **Debounced Search**: 300ms debounce on keystroke; immediate execution on Enter.
- **Collapsible Sections**: Interactive toggle (`collapsedSections`) with animated chevrons.
- **Accessible Multi-Select Controls**: Renders customized checkbox indicators with `role="checkbox"` and `:aria-checked`.
- **Scoped Reset Handling**: Reset buttons dispatch `@reset-filters` window events. In `/libraries/{slug}`, reset preserves the current digital library context.

### D. Layout & CSS Polish
Updated [resources/css/app.css](file:///c:/wamp64/www/portal/resources/css/app.css) and Blade components:
- **Card Clamping**: Added `-webkit-line-clamp: 2` to `.book h3` with a consistent `min-height: 2.7em` to keep grid rows aligned regardless of title length.
- **Metadata Badges**: Added `.pub-type-badge-sm` and `.pub-year-badge-sm` to display publication year and type cleanly on both grid cards and bibliographic list cards.
- **Mobile Drawer & Trigger**: Added `white-space: nowrap; flex-shrink: 0;` to `.mobile-filter-trigger` and enhanced the drawer header with active count badge, clear button, and close icon.

---

## 3. Changed Files

| File | Purpose / Modifications |
| :--- | :--- |
| [app/Services/CatalogueFilterService.php](file:///c:/wamp64/www/portal/app/Services/CatalogueFilterService.php) | **NEW:** Unified service for query building, parameter sanitization, multi-select filtering, access control, and dynamic facet count aggregation. |
| [app/Http/Controllers/CatalogueController.php](file:///c:/wamp64/www/portal/app/Http/Controllers/CatalogueController.php) | Refactored `explore()` and `libraryShow()` to use `CatalogueFilterService`; added library access boundary checks to `book()`. |
| [resources/js/app.js](file:///c:/wamp64/www/portal/resources/js/app.js) | Registered reusable `catalogueFaceted` Alpine component with history, stale-request protection, and multi-selection logic. |
| [resources/views/explore.blade.php](file:///c:/wamp64/www/portal/resources/views/explore.blade.php) | Cleaned `x-data` initialization using `@js($catalogueConfig)`; added collapsible headers, checkboxes, and removable active filter pills. |
| [resources/views/libraries/show.blade.php](file:///c:/wamp64/www/portal/resources/views/libraries/show.blade.php) | Scoped library catalogue using `catalogueFaceted` component; preserved library context on reset. |
| [resources/views/components/book-card.blade.php](file:///c:/wamp64/www/portal/resources/views/components/book-card.blade.php) | Added publication type and year tags; added title tooltips and line-clamp structure. |
| [resources/views/components/books-grid.blade.php](file:///c:/wamp64/www/portal/resources/views/components/books-grid.blade.php) | Updated zero-result empty state to dispatch `reset-filters` without navigating away from library. |
| [resources/views/components/books-list.blade.php](file:///c:/wamp64/www/portal/resources/views/components/books-list.blade.php) | Synchronized empty state reset button with `@reset-filters` event dispatch. |
| [resources/css/app.css](file:///c:/wamp64/www/portal/resources/css/app.css) | Added `.facet-header-btn`, `.facet-chevron`, `.facet-checkbox`, `.book-card-meta`, `.pub-year-badge-sm`, line clamping, and mobile trigger wrapping prevention. |
| [tests/Feature/StandardFacetedCatalogueTest.php](file:///c:/wamp64/www/portal/tests/Feature/StandardFacetedCatalogueTest.php) | Expanded test suite with 8 new feature tests covering multi-selection, cross-facet matrices, facet counts, access-control isolation, and parameter safety. |

---

## 4. Filter Interaction Matrix

| Active Facet 1 | Active Facet 2 | Active Facet 3 | Expected Logic & Behavior | Status |
| :--- | :--- | :--- | :--- | :---: |
| **Search (`q`)** | — | — | Searches across 11 fields; case-insensitive, trims extra whitespace. | **PASS** |
| **Library (`library`)** | **Discipline (`category`)** | — | Matches books in Library A AND Discipline X. Facet counts for types update. | **PASS** |
| **Discipline (`category`)** | **Publication Type (`type`)** | — | Matches Discipline X AND Type Y. | **PASS** |
| **Type (`type`)** | **Era (`year`)** | — | Matches Type Y AND Era Bucket Z (or custom year). | **PASS** |
| **Search (`q`)** | **Discipline (`category`)** | **Sort (`sort`)** | Matches query AND discipline; orders by selected sort (A-Z, year, etc.). | **PASS** |
| **Multi-Discipline** | — | — | `category=catA,catB` matches books in Cat A **OR** Cat B. | **PASS** |
| **Multi-Type** | **Multi-Era** | — | Matches (Type A **OR** Type B) **AND** (Era X **OR** Era Y). | **PASS** |
| **Search** | **Discipline** | **View (`view=list`)** | Renders bibliographic list layout with citations and tags; retains active filters. | **PASS** |
| **Any Filter** | **Sort** | **Browser Back** | Restores previous URL query parameters and re-renders matching results via `popstate`. | **PASS** |
| **Scoped Library** | **Active Filters** | **Reset All** | Clears search and facet selections while remaining on `/libraries/{slug}`. | **PASS** |
| **Invalid Params** | — | — | Non-numeric years, invalid sorts, or unexpected arrays return HTTP 200 without 500 error. | **PASS** |
| **Guest Visitor** | **Private Library** | — | Books in private libraries are excluded from catalogue and facet counts; direct URL 404s. | **PASS** |

---

## 5. Test Results

### Targeted Feature Test Suite: `StandardFacetedCatalogueTest.php`

```text
PASS  Tests\Feature\StandardFacetedCatalogueTest
✓ catalogue renders standard faceted sidebar layout
✓ catalogue filters by search query
✓ catalogue filters by library facet
✓ catalogue filters by publication type facet
✓ catalogue filters by year era facet
✓ catalogue sorting options
✓ catalogue supports grid and bibliographic list views
✓ catalogue ajax faceted filtering
✓ library show page renders standard faceted sidebar
✓ catalogue multi value filters within same facet
✓ catalogue combined cross facet filters matrix
✓ catalogue facet counts accurately reflect active filters
✓ catalogue access control and privacy isolation
✓ catalogue invalid parameters handled gracefully
✓ catalogue empty results state
✓ library show reset preserves library scope
✓ catalogue sorting preserves active filters

Tests:    17 passed (117 assertions)
Duration: 2.45s
```

### Full Portal Regression Test Suite

```text
PASS  Tests\Feature\CalibreImportEngineTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\ExecutivePdfPerformanceAndCoverTest
PASS  Tests\Feature\MultipleLibraryManagementTest
PASS  Tests\Feature\PortalP0SecurityAndIntegrityTest
PASS  Tests\Feature\PortalP1ReaderAndPublicationEngineTest
PASS  Tests\Feature\PortalP2AdminAndCalibreMigrationTest
PASS  Tests\Feature\PortalPriorityWorkflowTest
PASS  Tests\Feature\PortalVisualRecoveryTest
PASS  Tests\Feature\ProfessionalBookManagementTest
PASS  Tests\Feature\StandardFacetedCatalogueTest

Tests:    80 passed (504 assertions)
Duration: 31.40s
```

### Code Style Formatting: Laravel Pint

```text
Pint passed with 0 style violations across all modified files.
```

---

## 6. Screenshots & Visual Verification Evidence

All visual states were verified in a live headless Chromium environment against the local server (`http://127.0.0.1:8000`).

````carousel
![Desktop Catalogue Default](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\catalogue_desktop.png)
<!-- slide -->
![Search Query Active with Removable Chip](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\catalogue_search_active.png)
<!-- slide -->
![Bibliographic Academic List View](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\catalogue_list_view.png)
<!-- slide -->
![Reset All Filters Restored Full Catalogue](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\catalogue_reset.png)
<!-- slide -->
![Mobile Viewport Clean Layout](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\catalogue_mobile_default.png)
<!-- slide -->
![Mobile Filter Drawer Open](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\catalogue_mobile_drawer_open.png)
<!-- slide -->
![Individual Digital Library Scoped Page](C:\Users\USER\.gemini\antigravity-ide\brain\eb8d720b-d32a-427d-974f-d1b85ee27f6a\library_scoped_catalogue.png)
````

### Key Visual Observations:
1. **Desktop Catalogue**: High-contrast, executive aesthetic adhering strictly to the approved Modern Light palette (`--navy`, `--gold`, `--cream`). Grid cards align evenly with 2-line title clamps and clean metadata badges.
2. **Search & Chips**: Live search updates result tallies and facet counts instantly. Active filter bar renders removable chips with direct `×` removal.
3. **Bibliographic List View**: Cards display publication type badges, discipline tags, library badges, citation metadata, and `Read Publication →` buttons.
4. **Mobile Experience**: At 390px width, header controls and bottom navigation remain accessible. The "Filters" button does not wrap, and opening the drawer smoothly displays checkable facets with backdrop blur and Escape key dismissal.
5. **Scoped Library Page**: `/libraries/executive-core-library` displays collection branding, scoped title tally, and filtered search without leaking outside collections.

---

## 7. Remaining Limitations & Recommendations

1. **Full-Text PDF Content Search**: The current search engine operates across 11 database attributes (titles, subtitles, authors, tags, series, publishers, DOIs, ISBNs, descriptions, and category names). It does not index raw PDF body text. If full-text document search inside large PDFs is required in the future, PostgreSQL `tsvector` or Meilisearch indexing can be added to `CatalogueFilterService` without altering frontend views.
2. **Publication Year Index**: On large datasets (> 100,000 publications), a composite database index on `(status, published_at, publication_year)` will further accelerate conditional era aggregation.
3. **Multi-level Hierarchical Categories**: The current implementation supports primary categories/disciplines. If subcategories or deep taxonomies are introduced, the accordion facet structure can be expanded recursively.
