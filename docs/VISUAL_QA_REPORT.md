# Visual recovery QA report

Date: 16 October 2026 (Africa/Nairobi)
Project: `C:\wamp64\www\portal`
Reference: `prototype_backup/index.html` (preserved).

## Result

The public portal now uses the approved navy/gold/cream editorial identity, Playfair Display and DM Sans, with a two-column homepage and the real slider contained in its media column. Insight images and text occupy separate regions. The final Chrome run reviewed **85 page/viewport states** through the existing WAMP Apache installation, with HTTP 200 responses, **zero document overflow, zero Insight overlaps, zero broken visible images, zero console/runtime errors and zero failed requests**.

The full PHPUnit suite passed: **43 tests, 263 assertions**. The six new tests passed independently: **6 tests, 37 assertions**. Production asset build and the 92-route listing succeeded. No dependency, schema, seed, authentication policy or document-stream authorization changes were made. No production deployment was performed.

## Verified causes and repairs

1. **Hero composition:** `home.blade.php` selected a separate full-width banner whenever active slides existed. Its search dock used a negative top margin. A single two-column hero now always retains the approved headline, introduction, search and topic chips. Active/ordered slides remain in the right column; captions and controls occupy separate regions below the image.
2. **Insight overlap:** the flex card contained a fixed-height grid media box without containment. Intrinsic image sizing overflowed that box into the following body. The shared card uses a non-shrinking aspect-ratio media region, positioned contained images and a normal content flow. Category, full heading, optional excerpt and reading metadata remain readable.
3. **Duplicate navigation:** an inline `display:inline-flex` overrode the desktop `.menu` rule. Removing it and switching navigation at 1100px resolves the simultaneous desktop menu/toggle. The menu exposes expanded state and focus, closes with Escape/outside click, and resets when the desktop layout returns.
4. **Spacing:** the shared `.wrap` shorthand reset section block padding. An explicit `section.wrap` rule restores consistent vertical rhythm. This was an inherited style issue, not evidence that the slider itself introduced every spacing problem.
5. **Publication visibility:** the homepage filled Insight gaps with an unscoped query; listing and detail queries also omitted publication filtering. All three now use the existing published scope, preventing draft/scheduled content from appearing or being linked to publicly.
6. **Other presentation defects:** native book links replace click-only cards; covers retain their full image and proportions; registration fields and library grids fit mobile widths; article action links wrap; reader controls fit above/below the canvas. Public gold buttons use navy text (calculated normal-state contrast **5.24:1**) while retaining `#b98227`.
7. **Admin mobile clipping:** visual review caught an issue beyond the document-width metric: the hero table clipped its action columns and topbar actions squeezed offscreen. The table now uses the project's existing horizontal-scroll wrapper and the topbar groups wrap on mobile. Keyboard focus can reach the Edit action inside the scroller. CRUD forms and routes remain intact.

## Screenshot comparison evidence

All PNGs are actual Chrome captures. Images are existing uploaded application media; no generated artwork or client content was added.

| Subject | Before/reference | Recovered |
| --- | --- | --- |
| Desktop homepage, including navigation, featured book and Insight cards | [Approved prototype](visual-qa/prototype-1440.png), [current portal before changes](visual-qa/before-1440.png) | [Final homepage](visual-qa/home-1440.png), [hero detail](visual-qa/recovered-hero-1440.png) |
| Mobile homepage | [Approved prototype](visual-qa/prototype-390.png), [portal before changes](visual-qa/before-390.png) | [Final homepage](visual-qa/home-390.png), [viewport](visual-qa/home-390-viewport.png) |
| Executive Insights | Card overlap is visible in the desktop before capture above | [Recovered cards](visual-qa/recovered-insights-1440.png), [listing](visual-qa/insights-1440.png) |
| Featured publication | See homepage before capture | [Preserved uploaded cover and native book link](visual-qa/recovered-books-1440.png) |
| Mobile menu | See before homepage | [Expanded menu](visual-qa/mobile-menu-390-viewport.png) |
| Publication detail | Separate pre-change detail capture was not collected | [Desktop](visual-qa/publication-1440.png), [mobile](visual-qa/publication-390.png) |
| Full Insight article | Separate pre-change article capture was not collected | [Desktop](visual-qa/article-1440.png), [mobile](visual-qa/article-390.png) |
| Reader with rendered PDF | Separate pre-change reader capture was not collected | [Desktop](visual-qa/reader-1440.png), [mobile](visual-qa/reader-390-viewport.png) |
| Hero-slide administration | The initial mobile review exposed clipped columns | [Desktop](visual-qa/admin-hero-slides-1440.png), [mobile](visual-qa/admin-hero-slides-390-viewport.png), [mobile action columns](visual-qa/admin-hero-slide-actions-390-viewport.png) |

Prototype screenshots exist at all seven required widths. The actual pre-edit portal baseline was captured at 1440px and 390px; there are no invented secondary-page before screenshots. After screenshots cover every listed page at every required viewport.

## Pages and responsive results

Each of the following was checked at all seven viewports: Home, Explore Library, Categories, Category Details, Executive Insights, full Insight article, Publication Details, My Library, Login, Registration, authenticated Reader, and Admin Hero Slide Management. An additional authenticated My Library state was checked at 390px.

| Viewport | Page states | Homepage navigation | Document overflow | Insight overlap |
| --- | ---: | --- | ---: | ---: |
| 1920x1080 | 12 | Primary navigation | 0 | 0 |
| 1440x900 | 12 | Primary navigation | 0 | 0 |
| 1366x768 | 12 | Primary navigation | 0 | 0 |
| 1024x768 | 12 | Menu toggle | 0 | 0 |
| 768x1024 | 12 | Menu toggle | 0 | 0 |
| 390x844 | 13 | Menu toggle | 0 | 0 |
| 375x812 | 12 | Menu toggle | 0 | 0 |

Desktop retains balanced columns. At 1024px the hero still has two columns and the public header uses its compact menu. At 768px the hero stacks and Insights use two columns. At 390px and 375px the hero stacks, Insights use one column, and search, form fields and article controls fit. Public bottom navigation has reserved body space and safe-area padding. The PDF reader uses its separate controls and has no public bottom navigation.

Visual review used the full screenshots plus focused hero/cards, mobile menu, publication/article/forms, actual rendered PDF and admin screenshots. Passing width metrics alone was not treated as sufficient; the admin table issue was found and corrected in this review.

## Interaction and functional verification

- Slider previous/next, dots and explicit pause work. Reduced-motion preference starts with autoplay disabled. A separate real-time check confirmed 6.5-second autoplay; all three panel heights at 1440px measured **537.17px**, without a slide-change jump.
- Hero search submitted `q=MFANO` and returned the existing publication. Topic chips submitted the correct query. Book and Insight cards were clicked and reached their detail pages.
- Mobile menu opens, moves focus to Home, closes on Escape and returns focus to the toggle. Desktop primary links and mobile toggle visibility were measured at every viewport. Admin access links remain role-aware.
- All existing uploaded hero/Insight/cover media loaded. Supplemental checks dispatched image errors and verified that hero, Insight and cover image fallbacks become visible without removing their reserved layout regions.
- Existing public-book guest access was preserved. Restricted-reader authentication/authorization redirects are covered by the existing P0/P1 tests; the live catalogue's sole publication is public, so it correctly remains guest-readable.
- Admin login succeeded using the existing local demo login UI. Hero management displayed the existing three slides and kept edit/status/delete actions. No live slides were created, deleted or reordered during QA.
- The real two-page publication rendered successfully at all seven viewport sizes through WAMP. Existing tests cover reading progress, bookmarks, My Library, document authorization, lifecycle filtering, imports and the P0/P1/P2 workflows. Live reading may update the signed-in account's normal reading progress; no content records or seeds were reset.

Machine evidence: [final results](visual-qa/results.json), [autoplay/panel stability](visual-qa/slider-stability.json), [link/fallback checks](visual-qa/supplemental.json).

## Automated checks

| Check | Result |
| --- | --- |
| `php artisan test --compact` | PASS: 43 tests, 263 assertions, final run 29.1s |
| Focused `PortalVisualRecoveryTest.php` | PASS: 6 tests, 37 assertions |
| `npm run build` | PASS: Vite 8.3.4, 60 modules, final CSS/JS and manifests written to `public/build` |
| `php artisan route:list --except-vendor --no-interaction` | PASS: 92 routes; no route changes |
| Pint on the changed PHP controllers/test | PASS; focused test line endings formatted |
| `node tests/visual_recovery_qa.mjs` on WAMP; final private-page recheck with `--resume` | PASS: 85 states, no overflow/overlap/errors/failed requests; mobile admin action reachability asserted |

`pint --dirty --format agent` was attempted but stalled while resolving the parent repository, because this portal is an untracked directory beneath a broader Git root. It was stopped and Pint was successfully run against the exact changed PHP files. No unrelated parent-workspace files were formatted.

## Exact source/document files modified

| File | Change |
| --- | --- |
| `app/Http/Controllers/HomeController.php` | Remove the fallback that exposed draft or scheduled Insights. |
| `app/Http/Controllers/CatalogueController.php` | Apply the existing published scope to Insight listing and detail routes. |
| `resources/css/app.css` | Centralize public tokens, hero/card media containment, section rhythm, responsive navigation, covers, forms, reader controls and the small admin responsive fixes. |
| `resources/js/app.js` | Reusable Alpine navigation and carousel behavior, keyboard focus, pause and reduced motion. |
| `resources/views/home.blade.php` | Restore the two-column hero, right-column slider, search, bookshelf fallback and appropriate shelf empty state. |
| `resources/views/layouts/app.blade.php` | Scope the public shell, remove forced desktop hamburger, add navigation semantics, skip link and role-aware mobile links. |
| `resources/views/components/insight-card.blade.php` | New shared editorial card with separate media, text, excerpt and reading metadata. |
| `resources/views/components/book-card.blade.php` | Replace click-only articles with keyboard-accessible native links. |
| `resources/views/components/cover.blade.php` | Stable cover proportions, uploaded-image containment, alt text and fallback cover. |
| `resources/views/insights/index.blade.php` | Use the shared card and provide a published-content empty state. |
| `resources/views/insights/show.blade.php` | Stable article image, readable body and wrapping article actions. |
| `resources/views/explore.blade.php` | Add accessible labels to catalogue selectors. |
| `resources/views/auth/register.blade.php` | Stack paired registration fields on narrow screens. |
| `resources/views/library.blade.php` | Allow saved/progress/bookmark grids to fit narrow screens. |
| `resources/views/reader.blade.php` | Scope reader mobile styling and label zoom/bookmark controls. |
| `resources/views/layouts/admin.blade.php` | Add classes to the existing topbar groups so mobile actions wrap. |
| `resources/views/admin/hero-slides/index.blade.php` | Reuse the existing responsive table wrapper so edit/status/delete columns remain reachable. |
| `tests/Feature/PortalVisualRecoveryTest.php` | Six focused regression tests. |
| `tests/visual_recovery_qa.mjs` | Repeatable real-Chrome screenshot, layout and interaction checks. |
| `docs/IMPLEMENTATION_PROGRESS.md` | Append the recovery status and verification. |
| `docs/ENGINEERING_HANDOFF.md` | Append implementation, reproduction and runtime notes. |
| `docs/VISUAL_QA_REPORT.md` | This evidence report. |

Generated artifacts: `public/build/manifest.json`, `public/build/fonts-manifest.json`, the CSS/JS/font assets referenced by those manifests, and PNG/JSON QA evidence under `docs/visual-qa/`. Existing docs were appended, not replaced. `prototype_backup/index.html`, routes, models, migrations, policies, admin controllers and application dependencies were not changed.

## Intentional differences from the prototype

The original static bookshelf is retained when no active slide exists. When slides exist, real CMS images/captions replace the bookshelf only within the media column; the overall hero is taller to accommodate readable captions outside the image. Insight cards use real uploaded images and excerpts. Cover images use containment so their full artwork remains visible. Public gold buttons have navy ink for small-text contrast. Navigation adapts earlier because the real application's labels and role actions need more space. Real counts/content replace prototype samples; the current catalogue has one published title, so no books or statistics were fabricated to fill the shelf.

## Remaining issues and practical limits

- **PHP preview transport:** repeated PDF visits on `php artisan serve` at port 8001 intermittently produced Chrome `ERR_INVALID_HTTP_RESPONSE`. Fresh HTTP replays returned valid 200 responses with 162,536 bytes, and disabling browser cache did not eliminate the preview-server failure. The same application/PDF completed ten consecutive renders and the full seven-viewport reader matrix on existing WAMP Apache. This isolates a preview-transport difference, but does not establish its lower-level cause. Diagnostics are preserved in [the failed preview attempt](visual-qa/php-preview-attempt.json). Use WAMP for review; investigate the PHP/Chrome preview transport separately if port 8001 is required. No streaming/authentication logic was changed to hide this issue.
- Build emits the existing optional `fontaine` optimized-font-fallback warning. Build succeeds; no dependency was added.
- Real Chrome was used. Safari, Firefox, physical touch devices and production hosting were not tested. This is a local visual recovery, not production deployment approval.
- Separate pre-change screenshots of secondary pages were not collected; the table above identifies the available before evidence precisely.

## Reproduction

Run the normal suite and build from the portal root. For browser QA, use the existing local WAMP installation:

```powershell
$env:PORTAL_QA_BASE_URL = 'http://localhost/portal/public'
node tests/visual_recovery_qa.mjs
```

The QA script uses installed `puppeteer-core` and installed Chrome, and the existing demo Auto-fill login control. It neither installs browsers/dependencies nor runs migrations against the live database. Its default target remains the configured port 8001 preview; set the environment variable above for the verified WAMP run.


## Follow-up: book-cover URL regression corrected

The owner reported missing covers after the temporary port-8001 preview was stopped. Browser inspection reproduced the failure: the page was served by WAMP, but `Book::getCoverUrlAttribute()` used the public disk's configured `APP_URL`, producing `http://127.0.0.1:8001/storage/...`. Earlier WAMP checks had passed while that separate preview was still running, so they missed this cross-server dependency.

`app/Models/Book.php` now generates local public cover URLs with the same request-aware `asset('storage/...')` approach used by the hero/Insight images. This preserves the request host, scheme and WAMP subdirectory without changing saved cover paths. The model is an additional modified file beyond the original inventory above. `tests/Feature/PortalVisualRecoveryTest.php` now includes a host/subdirectory regression case.

Verified with the preview server off: homepage, catalogue and publication details all load the real cover from WAMP, on the same origin. Desktop/mobile screenshots: [before](visual-qa/cover-loading-before.png), [after](visual-qa/cover-loading-after.png), [mobile after](visual-qa/cover-loading-after-mobile.png). [Browser request evidence](visual-qa/cover-url-regression.json).

Updated verification: **44 tests, 266 assertions pass**; focused suite **7 tests, 40 assertions pass**. Pint passes for the model/test. This follow-up supersedes the earlier statement that models were unchanged and the earlier automated-test totals. No assets needed rebuilding for this server-rendered URL fix.
