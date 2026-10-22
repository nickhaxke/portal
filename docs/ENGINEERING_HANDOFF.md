# Executive Intelligence, Leadership & Lifelong Learning Portal
## Engineering Handoff & Technical Continuity Document

**Workspace:** `C:\wamp64\www\portal`  
**Current Milestone Completed:** Phase P2 — Complete Administration Control Center & Calibre Migration  
**Date:** October 15, 2026  
**Status:** All 37 Automated Tests Passing (226 Assertions), Zero Regressions, Live Server Verified  

---

## 1. Executive Summary

Phase P2 has transformed the bespoke Laravel administration into a comprehensive, multi-module Executive Library Control Center and engineered a high-reliability Calibre-to-Portal migration system. 

Key design and architecture rules strictly preserved:
- **Zero Filament:** All 13 administrative modules are crafted with custom Blade templates using clean HTML5 semantics, zero third-party admin panel overhead, and responsive styling.
- **Client Design Identity:** Retains the client-approved palette (Navy `#102946`, Gold `#b98227`, Cream `#f8f6f1`), Playfair Display editorial typography, and DM Sans application typography.
- **Data Integrity:** Real MySQL database queries powering all metrics. No hardcoded or fabricated statistics.
- **Controlled Migration:** Safe read-only SQLite extraction from Calibre libraries with SHA-256 duplicate detection and idempotent re-execution.

---

## 2. Models and Migrations

### Migrations Added
1. `database/migrations/2026_10_15_160000_add_p2_admin_governance_and_calibre_tables.php`:
   - Adds `deleted_at` to `books` for soft-deletion.
   - Adds `file_hash` (SHA-256) and `version_number` to `book_files`.
   - Creates `book_category` pivot table for multi-category attribution.
   - Creates `collections` table and `collection_book` pivot table with display ordering.
   - Creates `audit_logs` table (`user_id`, `event`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`).
   - Creates `import_batches` table (`batch_code`, `source_type`, `source_path`, `status`, `total_books`, `imported_count`, `duplicate_count`, `skipped_count`, `failed_count`, `log_output`).
   - Creates `portal_settings` table for runtime key-value configurations.
2. `database/migrations/2026_10_15_163000_add_calibre_id_to_books_table.php`:
   - Adds `calibre_id` index to `books` for idempotent source-system tracking.

### Eloquent Models Updated & Created
- `App\Models\Book`:
  - Added `SoftDeletes` trait.
  - Added `categories()` BelongsToMany relationship through `book_category`.
  - Added `collections()` BelongsToMany relationship through `collection_book`.
  - Upgraded `primaryPdf()` to use `ofMany(['id' => 'max'], fn($q) => $q->where('file_type', 'pdf')->where('is_active', true))` to ensure EPUB co-existence does not mask PDF reader files.
- `App\Models\BookFile`:
  - Added `file_hash` and `version_number` fillable fields.
- `App\Models\Collection`:
  - Curated anthologies with publication relationship (`books()`), slug generation, and cover management.
- `App\Models\AuditLog`:
  - Activity tracking with casts for JSON `old_values` and `new_values`.
- `App\Models\ImportBatch`:
  - Batch tracking with user relationship and status lifecycle.
- `App\Models\PortalSetting`:
  - Helper methods `get(key, default)` and `set(key, value)`.

---

## 3. Architecture & Service Layer

1. **`App\Services\CalibreMigrationService`**:
   - Safely parses SQLite `metadata.db` via read-only `PDO("sqlite:...")`.
   - Maps authors, tags, series, publishers, identifiers (ISBN, DOI), and synopsis.
   - Detects duplicates using both external `calibre_id` and SHA-256 file hashes (`hash_file('sha256', $path)`).
   - Copies PDF and EPUB attachments to secure private storage: `storage/app/private/books/{slug}/`.
   - Updates `import_batches` transactionally to ensure interrupted runs can be diagnosed and resumed.
2. **`App\Console\Commands\ImportCalibreCommand`**:
   - Signature: `php artisan portal:import-calibre {path} {--dry-run} {--limit=}`.
   - Supports headless migration for server/cPanel environments without long-running browser sessions.
3. **`App\Services\AuditLogService`**:
   - Captures who performed what operation, the target model, IP address, user agent, and mutation diffs.

---

## 4. Administrative Modules (P2A – P2G)

| # | Route URI | Controller | Description |
|---|---|---|---|
| 1 | `/admin` | `Admin\DashboardController@index` | Executive Overview with real metrics, inventory distribution, storage usage, and activity trail. |
| 2 | `/admin/books` | `Admin\BookController` | Complete publication management: lifecycle filters (All, Published, Draft, Archived, Trash), soft deletes, restore, force delete, bulk actions, version incrementation, and multi-authors. |
| 3 | `/admin/imports` | `Admin\ImportController` | Calibre Migration Engine: path validation, scan & preview, batch execution, and historical batch detail inspector. |
| 4 | `/admin/authors` | `Admin\AuthorController` | Author directory, search, biography management, and transactional author merge workflow with book re-attribution. |
| 5 | `/admin/categories`| `Admin\CategoryController` | Hierarchical disciplines and categories with circular parent-child validation and deletion guards. |
| 6 | `/admin/insights` | `Admin\InsightController` | CMS for executive articles, leadership briefings, reading times, and editorial publishing. |
| 7 | `/admin/collections`| `Admin\CollectionController` | Curated reading anthologies, publication assignment, reordering, and public featured toggles. |
| 8 | `/admin/readers` | `Admin\ReaderAdminController` | Reader account management: search, profile inspection, account suspension/activation, email verification, and reading progress review. |
| 9 | `/admin/administrators` | `Admin\AdministratorController` | Staff accounts, role assignments (`superadmin`, `admin`, `editor`, `viewer`), self-lockout prevention. |
| 10| `/admin/reports` | `Admin\ReportController` | Analytics on inventory, reader engagement, storage consumption, and CSV export (`/admin/reports/export-books`). |
| 11| `/admin/audit-logs` | `Admin\AuditLogController` | Comprehensive security audit trail with JSON diff inspectors for mutations. |
| 12| `/admin/settings` | `Admin\SettingController` | Dynamic system settings: portal name, registration policy (`open`, `approval_required`, `closed`), upload size limit. |
| 13| `/admin/backups` | `Admin\BackupController` | Database backup snapshots via `mysqldump` to private storage with CLI restoration guidance. |

---

## 5. Automated Verification Evidence

### PHPUnit Test Results
Command: `php artisan test`
- **Total Tests:** 37
- **Total Assertions:** 226
- **Status:** 100% Passed (0 Failures, 0 Errors, 0 Regressions)
- **Duration:** ~14.5 seconds

Test Suites Executed:
1. `tests/Feature/PortalP0SecurityAndIntegrityTest.php` (16 tests, 98 assertions)
2. `tests/Feature/PortalP1ReaderAndPublicationEngineTest.php` (10 tests, 43 assertions)
3. `tests/Feature/PortalP2AdminAndCalibreMigrationTest.php` (11 tests, 85 assertions)

### Live HTTP Verification (127.0.0.1:8001)
Tested via `tests/verify_endpoints.php`:
- `GET /admin/login` $\rightarrow$ 200 OK (CSRF verified)
- `POST /admin/login` $\rightarrow$ 302 Redirect to `/admin`
- `GET /admin` $\rightarrow$ 200 OK (Executive Intelligence Dashboard & 13 nav links active)
- `GET /admin/books` $\rightarrow$ 200 OK (Catalogue, lifecycle tabs & Add New Publication active)
- `GET /admin/imports` $\rightarrow$ 200 OK (Calibre Library Migration Engine active)
- `POST /admin/imports/scan` $\rightarrow$ 200 OK (Detected 'The Effective Executive' & 'Principles of Leadership' from synthetic fixture)
- `GET /admin/categories` $\rightarrow$ 200 OK (Disciplines & Categories active)
- `GET /admin/authors` $\rightarrow$ 200 OK (Authors & Contributors active)
- `GET /admin/readers` $\rightarrow$ 200 OK (Reader & Member Administration active)
- `GET /admin/audit-logs` $\rightarrow$ 200 OK (Audit trail active)
- `GET /admin/settings` $\rightarrow$ 200 OK (Portal Settings active)

### Frontend Asset Verification
Command: `npm run build`
- Vite v8.3.4
- 60 modules transformed cleanly
- Production assets generated in `public/build/` in 2.11s

### Multi-Viewport Visual QA & Navigation Matrix (P2-Recovery)
Tested via `tests/comprehensive_admin_qa.js`:
- **1440x900 Desktop**: Pinned header, 270px fixed sidebar with 13 modules, pinned user footer, and high-density metric cards. (`admin_overview_desktop.png`)
- **1366x768 Laptop**: Sidebar navigation smoothly scrollable without affecting topbar or dashboard content. (`admin_sidebar_navigable.png`)
- **768x1024 Tablet**: Clean header with hamburger toggle, drawer slide-out. (`admin_overview_tablet.png`)
- **390x844 Mobile**: Single column layout, zero horizontal overflow, drawer opens and closes with backdrop overlay. (`admin_overview_mobile.png`, `admin_mobile_drawer.png`)
- **13/13 Navigation Destinations Verified**: 100% reachable with HTTP 200, correct titles, active indicator, and zero console errors. (`admin_qa_results.json`)

---

## 6. Security Observations & Hard Safety Rules

1. **Private Storage Enforcement:** All PDF, EPUB, and database backup files remain strictly in `storage/app/private/`. Zero public symlink exposure exists for document assets.
2. **ACID Transaction Wrapping:** Publication updates, author merges, and Calibre imports execute inside `DB::transaction()` blocks to prevent orphan records upon partial failure.
3. **Migration Pre-Requisites:** Real client Calibre migration must only be executed after verifying available disk space and obtaining explicit owner authorization.

---

## 7. Next Executable Tasks (Phase P3)

The next engineer should proceed with **Phase P3: Production Operations & Executive Demonstration Polish**:
1. Review real-world cPanel hosting limits (upload limits, execution timeouts).
2. Prepare final client demonstration walkthrough script for Saturday, October 17, 2026.
3. Verify reader mobile touch responsiveness on iOS Safari and Android Chrome.


## 16 October 2026 ? Visual recovery handoff

The recovery is implemented in the existing Laravel portal at `C:\wamp64\www\portal`, separate from the `7` application. Read [VISUAL_QA_REPORT.md](VISUAL_QA_REPORT.md) before continuing; it contains the authoritative file list, evidence, responsive matrix and limitations.

- The homepage has a single two-column composition. Do not restore the separate full-width active-slide branch or dock search with a negative margin. Slider data/order/CTA filtering remain in the original controller/model; its Alpine behavior lives in `resources/js/app.js`.
- Both Insight lists use `components/insight-card.blade.php`. Its media owns the image dimensions and the body follows in normal flow. Use `x-on:error` for Alpine image errors in Blade: `@error` is Blade's validation directive and causes malformed compiled views in this context.
- Public chrome is scoped to `.site-header`, `.site-footer` and `.site-body`; the PDF reader and custom administration keep their separate shells. Public navigation switches at 1100px and uses explicit refs for keyboard focus. Gold button text in the public shell uses navy ink for contrast.
- Insight home/list/detail queries now respect the existing published scope. Do not fill public shelves with unscoped draft/scheduled records. No content was invented to fill the one-title live catalogue.
- The admin hero table reuses `.table-responsive`; its mobile action columns are horizontally reachable. Topbar context/action groups wrap below 600px. CRUD/security logic is unchanged.

Final automated verification: `php artisan test --compact` = **43 tests, 263 assertions**; focused recovery tests = **6 tests, 37 assertions**; `npm run build` succeeds; route listing = **92**. Pint succeeded for the exact changed PHP files after `--dirty` stalled on the broader parent Git repository.

Browser verification: **85 page/viewport states** on existing WAMP Apache with no document overflow, Insight overlap, console errors or failed requests. The reader displayed the real two-page PDF at every requested size. Autoplay, manual controls, reduced motion, menu focus/Escape, search/chips, card links, uploaded images and error fallbacks were checked.

Use the WAMP URL via `$env:PORTAL_QA_BASE_URL = 'http://localhost/portal/public'` before running `node tests/visual_recovery_qa.mjs`. The port-8001 PHP preview has an independently reproduced intermittent malformed PDF response; its lower-level transport cause remains unresolved. Do not loosen document authorization or replace working PDF logic to mask this local runtime issue. Chrome/WAMP passes ten consecutive PDF renders and the final matrix.

No migrations, dependency installation, framework replacement or production deployment was performed. Screenshots/diagnostics live under `docs/visual-qa/`; secondary-page pre-change screenshots were not collected and must not be presented as available evidence.


### Follow-up ? request-aware cover URLs

`app/Models/Book.php` is now also modified. Its cover accessor uses `asset('storage/'.ltrim($this->cover_image, '/'))`, consistent with local hero/Insight images. Do not restore the public disk's fixed preview-host URL for these local covers: WAMP uses a different origin/subdirectory, and the temporary port-8001 server is off. The earlier browser matrix ran while that server was still available; the new regression and browser check explicitly cover its absence. See `docs/visual-qa/cover-url-regression.json` and the QA report follow-up. Final suite now **44 tests, 266 assertions**; focused suite **7 tests, 40 assertions**.


### cPanel release package - 16 October 2026

Prepared `executive-portal-cpanel-2026-10-16.zip` (14.37 MiB), plus its SHA-256 file. See `docs/CPANEL-INSTALL-SW.md`. ZIP layout is sibling `portal_app/` and `public_html/`, extracted at the cPanel account HOME. It includes 76 locked production PHP packages, built frontend assets, uploads/private PDFs and a MySQL SQL snapshot. Runtime caches, local .env/key, sessions, reset tokens and development dependencies are excluded.

Release-specific changes exist only in the package: bootstrap uses sibling public_html for public_path; index.php points to portal_app; demo login autofill/panel removed; production environment template supplied; admin passwords rotated only in the SQL copy, with credentials in the ZIP's private INSTALL-PRIVATE.txt. The local source/database/admin credentials are unchanged. The SQL account export excludes the original admin hashes and remember tokens.

Checks passed: native database export, credential/hash match, production Composer platform requirements on PHP 8.4, PHP syntax, package discovery, 92 routes, production HTTP-kernel home/explore/login rendering, portable public_html/cover URLs, full ZIP CRC and per-file SHA-256 integrity. Target cPanel deployment and SQL import have not been performed; domain, database credentials, HTTPS, SMTP, permissions and storage link must be configured there.
