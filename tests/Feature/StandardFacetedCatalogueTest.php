<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandardFacetedCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected Library $libraryA;

    protected Library $libraryB;

    protected Category $catLeadership;

    protected Category $catTech;

    protected Author $authorAlpha;

    protected Author $authorBeta;

    protected Book $book1;

    protected Book $book2;

    protected Book $book3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->libraryA = Library::firstOrCreate(
            ['slug' => 'executive-core-library'],
            [
                'name' => 'Executive Core Library',
                'description' => 'Flagship collection of executive leadership books.',
                'visibility' => 'public',
                'is_published' => true,
                'sort_order' => 1,
            ]
        );

        $this->libraryB = Library::firstOrCreate(
            ['slug' => 'policy-strategy-vault'],
            [
                'name' => 'Policy & Strategy Vault',
                'description' => 'Governmental and corporate policy documentation.',
                'visibility' => 'public',
                'is_published' => true,
                'sort_order' => 2,
            ]
        );

        $this->catLeadership = Category::firstOrCreate(
            ['slug' => 'strategic-leadership'],
            [
                'name' => 'Strategic Leadership',
                'icon' => 'strategy',
                'sort_order' => 1,
            ]
        );

        $this->catTech = Category::firstOrCreate(
            ['slug' => 'digital-transformation'],
            [
                'name' => 'Digital Transformation',
                'icon' => 'explore',
                'sort_order' => 2,
            ]
        );

        $this->authorAlpha = Author::firstOrCreate(
            ['slug' => 'dr-alpha-vance'],
            [
                'name' => 'Dr. Alpha Vance',
            ]
        );

        $this->authorBeta = Author::firstOrCreate(
            ['slug' => 'prof-beta-montgomery'],
            [
                'name' => 'Prof. Beta Montgomery',
            ]
        );

        $this->book1 = Book::create([
            'library_id' => $this->libraryA->id,
            'category_id' => $this->catLeadership->id,
            'author_id' => $this->authorAlpha->id,
            'title' => 'Alpha Governance Frameworks',
            'slug' => 'alpha-governance-frameworks',
            'author_name' => $this->authorAlpha->name,
            'publication_type' => 'book',
            'publication_year' => 2026,
            'status' => 'published',
            'published_at' => now(),
            'access_policy' => 'open',
            'isbn' => '978-0-123456-78-9',
            'publisher' => 'Executive Press',
            'series' => 'Boardroom Governance Series',
            'doi' => '10.1000/alpha.gov.2026',
            'description' => 'Comprehensive manual on board-level governance.',
        ]);
        $this->book1->syncTags(['governance', 'leadership-board']);

        $this->book2 = Book::create([
            'library_id' => $this->libraryA->id,
            'category_id' => $this->catTech->id,
            'author_id' => $this->authorBeta->id,
            'title' => 'Beta AI & Enterprise Strategy',
            'slug' => 'beta-ai-enterprise-strategy',
            'author_name' => $this->authorBeta->name,
            'publication_type' => 'research_paper',
            'publication_year' => 2023,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'access_policy' => 'open',
            'isbn' => '978-0-987654-32-1',
            'publisher' => 'Tech Horizon Publishing',
            'series' => 'Artificial Intelligence Innovations',
            'doi' => '10.2000/beta.ai.2023',
            'description' => 'Peer-reviewed research paper on technological innovation.',
        ]);
        $this->book2->syncTags(['machine-learning', 'tech-strategy']);

        $this->book3 = Book::create([
            'library_id' => $this->libraryB->id,
            'category_id' => $this->catLeadership->id,
            'author_id' => $this->authorAlpha->id,
            'title' => 'Gamma Institutional Policy Review',
            'slug' => 'gamma-institutional-policy-review',
            'author_name' => $this->authorAlpha->name,
            'publication_type' => 'policy_document',
            'publication_year' => 2018,
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'access_policy' => 'open',
            'isbn' => '978-0-112233-44-5',
            'publisher' => 'Public Policy Institute',
            'series' => 'Statutory Review Papers',
            'doi' => '10.3000/gamma.policy.2018',
            'description' => 'State and corporate statutory policy assessment.',
        ]);
        $this->book3->syncTags(['public-policy', 'statutory-law']);
    }

    /**
     * Test catalogue renders the standard faceted sidebar layout with facets and controls.
     */
    public function test_catalogue_renders_standard_faceted_sidebar_layout(): void
    {
        $response = $this->get(route('explore'));

        $response->assertOk();
        $response->assertSee('catalogue-layout');
        $response->assertSee('catalogue-sidebar');
        $response->assertSee('Catalog Filters');
        $response->assertSee('Digital Libraries');
        $response->assertSee('Disciplines');
        $response->assertSee('Publication Types');
        $response->assertSee('Publication Era');
        $response->assertSee('Sort by:');
        $response->assertSee('view-switchers');
    }

    /**
     * Test faceted catalogue filters by search query.
     */
    public function test_catalogue_filters_by_search_query(): void
    {
        $response = $this->get(route('explore', ['q' => 'Governance']));

        $response->assertOk();
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertDontSee('Beta AI & Enterprise Strategy');
    }

    /**
     * Test faceted catalogue filters by digital library.
     */
    public function test_catalogue_filters_by_library_facet(): void
    {
        $response = $this->get(route('explore', ['library' => $this->libraryB->slug]));

        $response->assertOk();
        $response->assertSee('Gamma Institutional Policy Review');
        $response->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * Test faceted catalogue filters by publication type.
     */
    public function test_catalogue_filters_by_publication_type_facet(): void
    {
        $response = $this->get(route('explore', ['type' => 'research_paper']));

        $response->assertOk();
        $response->assertSee('Beta AI & Enterprise Strategy');
        $response->assertDontSee('Alpha Governance Frameworks');
        $response->assertDontSee('Gamma Institutional Policy Review');
    }

    /**
     * Test faceted catalogue filters by publication era.
     */
    public function test_catalogue_filters_by_year_era_facet(): void
    {
        // 2025-2026 should match book1 (2026)
        $response = $this->get(route('explore', ['year' => '2025-2026']));
        $response->assertOk();
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertDontSee('Beta AI & Enterprise Strategy');

        // 2020-2024 should match book2 (2023)
        $response2 = $this->get(route('explore', ['year' => '2020-2024']));
        $response2->assertOk();
        $response2->assertSee('Beta AI & Enterprise Strategy');
        $response2->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * Test catalogue sorting options.
     */
    public function test_catalogue_sorting_options(): void
    {
        // Title Ascending
        $responseAsc = $this->get(route('explore', ['sort' => 'title_asc']));
        $responseAsc->assertOk();

        // Title Descending
        $responseDesc = $this->get(route('explore', ['sort' => 'title_desc']));
        $responseDesc->assertOk();

        // Year Descending
        $responseYearDesc = $this->get(route('explore', ['sort' => 'year_desc']));
        $responseYearDesc->assertOk();
    }

    /**
     * Test switching between grid and bibliographic list views.
     */
    public function test_catalogue_supports_grid_and_bibliographic_list_views(): void
    {
        // Grid View
        $responseGrid = $this->get(route('explore', ['view' => 'grid']));
        $responseGrid->assertOk();
        $responseGrid->assertSee('books');

        // Bibliographic List View
        $responseList = $this->get(route('explore', ['view' => 'list']));
        $responseList->assertOk();
        $responseList->assertSee('books-list-view');
        $responseList->assertSee('book-list-item');
        $responseList->assertSee('Read Publication');
    }

    /**
     * Test AJAX live fetching returns updated HTML and counts.
     */
    public function test_catalogue_ajax_faceted_filtering(): void
    {
        // AJAX request for list view
        $response = $this->getJson(route('explore', [
            'q' => 'Strategy',
            'view' => 'list',
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'html',
            'count',
            'pagination',
        ]);
        $response->assertJson([
            'count' => 1,
        ]);
        $this->assertStringContainsString('Beta AI', $response->json('html'));
        $this->assertStringContainsString('books-list-view', $response->json('html'));
    }

    /**
     * Test library show page renders scoped standard faceted sidebar layout.
     */
    public function test_library_show_page_renders_standard_faceted_sidebar(): void
    {
        $response = $this->get(route('libraries.show', $this->libraryA->slug));

        $response->assertOk();
        $response->assertSee('catalogue-layout');
        $response->assertSee('catalogue-sidebar');
        $response->assertSee('Executive Core Library');
        $response->assertSee('Catalog Filters');
        $response->assertSee('Disciplines');
        $response->assertSee('Publication Types');
        $response->assertSee('Sort by:');
        $response->assertSee('view-switchers');

        // Scoped to library A only
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertSee('Beta AI & Enterprise Strategy');
        $response->assertDontSee('Gamma Institutional Policy Review');
    }

    /**
     * Test multi-value selections within the same facet (OR logic within facet).
     */
    public function test_catalogue_multi_value_filters_within_same_facet(): void
    {
        // Multi-category (Disciplines) comma-delimited
        $responseCats = $this->get(route('explore', [
            'category' => "{$this->catLeadership->slug},{$this->catTech->slug}",
        ]));
        $responseCats->assertOk();
        $responseCats->assertSee('Alpha Governance Frameworks');
        $responseCats->assertSee('Beta AI & Enterprise Strategy');
        $responseCats->assertSee('Gamma Institutional Policy Review');

        // Multi-category as array
        $responseCatsArray = $this->get(route('explore', [
            'category' => [$this->catLeadership->slug, $this->catTech->slug],
        ]));
        $responseCatsArray->assertOk();
        $responseCatsArray->assertSee('Alpha Governance Frameworks');
        $responseCatsArray->assertSee('Beta AI & Enterprise Strategy');

        // Multi-type: book and research_paper
        $responseTypes = $this->get(route('explore', [
            'type' => 'book,research_paper',
        ]));
        $responseTypes->assertOk();
        $responseTypes->assertSee('Alpha Governance Frameworks');
        $responseTypes->assertSee('Beta AI & Enterprise Strategy');
        $responseTypes->assertDontSee('Gamma Institutional Policy Review');

        // Multi-era: 2025-2026 and 2015-2019
        $responseEras = $this->get(route('explore', [
            'year' => '2025-2026,2015-2019',
        ]));
        $responseEras->assertOk();
        $responseEras->assertSee('Alpha Governance Frameworks'); // 2026
        $responseEras->assertSee('Gamma Institutional Policy Review'); // 2018
        $responseEras->assertDontSee('Beta AI & Enterprise Strategy'); // 2023
    }

    /**
     * Test combined cross-facet filter matrix uses correct AND logic across facets.
     */
    public function test_catalogue_combined_cross_facet_filters_matrix(): void
    {
        // 1. Library + Discipline
        $response1 = $this->get(route('explore', [
            'library' => $this->libraryA->slug,
            'category' => $this->catLeadership->slug,
        ]));
        $response1->assertOk();
        $response1->assertSee('Alpha Governance Frameworks'); // Library A + Leadership
        $response1->assertDontSee('Beta AI & Enterprise Strategy'); // Library A + Tech
        $response1->assertDontSee('Gamma Institutional Policy Review'); // Library B + Leadership

        // 2. Discipline + Publication Type
        $response2 = $this->get(route('explore', [
            'category' => $this->catTech->slug,
            'type' => 'research_paper',
        ]));
        $response2->assertOk();
        $response2->assertSee('Beta AI & Enterprise Strategy');
        $response2->assertDontSee('Alpha Governance Frameworks');

        // 3. Publication Type + Publication Era
        $response3 = $this->get(route('explore', [
            'type' => 'book',
            'year' => '2025-2026',
        ]));
        $response3->assertOk();
        $response3->assertSee('Alpha Governance Frameworks');
        $response3->assertDontSee('Beta AI & Enterprise Strategy');

        // 4. Search + Discipline + Sorting
        $response4 = $this->get(route('explore', [
            'q' => 'Governance',
            'category' => $this->catLeadership->slug,
            'sort' => 'title_asc',
        ]));
        $response4->assertOk();
        $response4->assertSee('Alpha Governance Frameworks');
        $response4->assertDontSee('Beta AI & Enterprise Strategy');

        // 5. Search + Multiple Facets (Library + Category + Type)
        $response5 = $this->get(route('explore', [
            'q' => 'Enterprise',
            'library' => $this->libraryA->slug,
            'category' => $this->catTech->slug,
            'type' => 'research_paper',
        ]));
        $response5->assertOk();
        $response5->assertSee('Beta AI & Enterprise Strategy');
        $response5->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * Test facet counts accurately reflect active filters and real records without N+1.
     */
    public function test_catalogue_facet_counts_accurately_reflect_active_filters(): void
    {
        // When filtered by Library B:
        $response = $this->getJson(route('explore', [
            'library' => $this->libraryB->slug,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'html',
            'count',
            'pagination',
            'facet_counts' => [
                'total',
                'categories',
                'types',
                'years',
            ],
        ]);

        $facetCounts = $response->json('facet_counts');
        // Total matching in library B is 1 (Gamma)
        $this->assertEquals(1, $facetCounts['total']);
        // Leadership category has 1, Tech category has 0
        $this->assertEquals(1, $facetCounts['categories'][$this->catLeadership->id] ?? 0);
        $this->assertEquals(0, $facetCounts['categories'][$this->catTech->id] ?? 0);
        // Publication type policy_document has 1, research_paper has 0
        $this->assertEquals(1, $facetCounts['types']['policy_document'] ?? 0);
        $this->assertEquals(0, $facetCounts['types']['research_paper'] ?? 0);
    }

    /**
     * Test access-control boundaries: draft, archived, and private publications/libraries are isolated.
     */
    public function test_catalogue_access_control_and_privacy_isolation(): void
    {
        // 1. Create a draft book in Library A
        $draftBook = Book::create([
            'library_id' => $this->libraryA->id,
            'category_id' => $this->catLeadership->id,
            'author_id' => $this->authorAlpha->id,
            'title' => 'Secret Board Manual (Draft)',
            'slug' => 'secret-board-manual-draft',
            'author_name' => $this->authorAlpha->name,
            'status' => 'draft',
            'publication_type' => 'book',
            'publication_year' => 2026,
            'visibility' => 'public',
        ]);

        // 2. Create a private library and book
        $privateLib = Library::create([
            'name' => 'Restricted Council Archives',
            'slug' => 'restricted-council-archives',
            'visibility' => 'private',
            'is_published' => true,
            'sort_order' => 10,
        ]);

        $privateBook = Book::create([
            'library_id' => $privateLib->id,
            'category_id' => $this->catLeadership->id,
            'author_id' => $this->authorAlpha->id,
            'title' => 'Classified Sovereign Treaty Analysis',
            'slug' => 'classified-sovereign-treaty-analysis',
            'author_name' => $this->authorAlpha->name,
            'status' => 'published',
            'publication_type' => 'book',
            'publication_year' => 2026,
            'visibility' => 'public',
        ]);

        // Public visitor accessing /explore:
        $guestResponse = $this->get(route('explore'));
        $guestResponse->assertOk();
        $guestResponse->assertDontSee('Secret Board Manual (Draft)');
        $guestResponse->assertDontSee('Classified Sovereign Treaty Analysis');
        $guestResponse->assertDontSee('Restricted Council Archives');

        // Facet counts do not leak draft or private book counts
        $guestAjax = $this->getJson(route('explore'));
        $this->assertEquals(3, $guestAjax->json('count')); // Exactly the 3 published public books

        // Direct show of private library by guest returns 404
        $privateLibResponse = $this->get(route('libraries.show', $privateLib->slug));
        $privateLibResponse->assertNotFound();

        // Direct show of book in private library by guest returns 404
        $privateBookResponse = $this->get(route('books.show', $privateBook->slug));
        $privateBookResponse->assertNotFound();
    }

    /**
     * Test invalid query parameters do not produce 500 errors.
     */
    public function test_catalogue_invalid_parameters_handled_gracefully(): void
    {
        $response = $this->get(route('explore', [
            'q' => str_repeat('a', 500),
            'year' => 'invalid-year-format',
            'category' => ['unexpected', 'array'],
            'type' => ['unexpected', 'type'],
            'sort' => 'malicious_order_by_injection',
            'page' => -99,
        ]));

        $response->assertOk();
        $response->assertSee('catalogue-layout');
    }

    /**
     * Test empty results state renders informative feedback and reset option.
     */
    public function test_catalogue_empty_results_state(): void
    {
        $response = $this->get(route('explore', ['q' => 'NonExistentTitleQuery999']));

        $response->assertOk();
        $response->assertSee('No books match your search or filter criteria.');
        $response->assertSee('Reset All Filters');
    }

    /**
     * Test resetting filters on library show page preserves library context.
     */
    public function test_library_show_reset_preserves_library_scope(): void
    {
        $response = $this->get(route('libraries.show', [
            'slug' => $this->libraryA->slug,
            'q' => 'NonExistentSearchTerm123',
        ]));

        $response->assertOk();
        $response->assertSee('Executive Core Library');
        $response->assertSee('Reset All Filters');
    }

    /**
     * Test sorting preserves all active filters in query strings and live fetching.
     */
    public function test_catalogue_sorting_preserves_active_filters(): void
    {
        $response = $this->get(route('explore', [
            'q' => 'Strategy',
            'library' => $this->libraryA->slug,
            'category' => $this->catTech->slug,
            'sort' => 'title_desc',
        ]));

        $response->assertOk();
        $response->assertSee('Beta AI & Enterprise Strategy');
        $response->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * 1. Test All Fields search finds matching metadata across title, author, publisher, series, tags, and identifiers.
     */
    public function test_search_all_fields_matches_various_metadata(): void
    {
        // By Title
        $respTitle = $this->get(route('explore', ['q' => 'Governance', 'search_by' => 'all']));
        $respTitle->assertOk();
        $respTitle->assertSee('Alpha Governance Frameworks');

        // By Author
        $respAuthor = $this->get(route('explore', ['q' => 'Montgomery', 'search_by' => 'all']));
        $respAuthor->assertOk();
        $respAuthor->assertSee('Beta AI & Enterprise Strategy');

        // By Publisher
        $respPub = $this->get(route('explore', ['q' => 'Executive Press', 'search_by' => 'all']));
        $respPub->assertOk();
        $respPub->assertSee('Alpha Governance Frameworks');

        // By Series
        $respSeries = $this->get(route('explore', ['q' => 'Statutory Review Papers', 'search_by' => 'all']));
        $respSeries->assertOk();
        $respSeries->assertSee('Gamma Institutional Policy Review');

        // By Tag
        $respTag = $this->get(route('explore', ['q' => 'machine-learning', 'search_by' => 'all']));
        $respTag->assertOk();
        $respTag->assertSee('Beta AI & Enterprise Strategy');

        // Legacy compatibility: search_by omitted defaults to All Fields
        $respLegacy = $this->get(route('explore', ['q' => 'Horizon Publishing']));
        $respLegacy->assertOk();
        $respLegacy->assertSee('Beta AI & Enterprise Strategy');
    }

    /**
     * 2. Test Title search excludes records matching only an author's name.
     */
    public function test_search_title_excludes_author_matches(): void
    {
        // "Vance" is the author of Book 1 and Book 3, but is NOT in any title
        $response = $this->get(route('explore', ['q' => 'Vance', 'search_by' => 'title']));
        $response->assertOk();
        $response->assertDontSee('Alpha Governance Frameworks');
        $response->assertDontSee('Gamma Institutional Policy Review');

        // Title search with "Governance" finds Book 1
        $response2 = $this->get(route('explore', ['q' => 'Governance', 'search_by' => 'title']));
        $response2->assertOk();
        $response2->assertSee('Alpha Governance Frameworks');
        $response2->assertDontSee('Beta AI & Enterprise Strategy');
    }

    /**
     * 3. Test Author search excludes records matching only titles and matches multi-authors.
     */
    public function test_search_author_excludes_title_matches_and_matches_linked_coauthors(): void
    {
        // "Governance" is in Book 1's title, but NOT an author's name
        $response = $this->get(route('explore', ['q' => 'Governance', 'search_by' => 'author']));
        $response->assertOk();
        $response->assertDontSee('Alpha Governance Frameworks');

        // "Vance" is in Dr. Alpha Vance's name -> matches Book 1 and Book 3
        $response2 = $this->get(route('explore', ['q' => 'Vance', 'search_by' => 'author']));
        $response2->assertOk();
        $response2->assertSee('Alpha Governance Frameworks');
        $response2->assertSee('Gamma Institutional Policy Review');
        $response2->assertDontSee('Beta AI & Enterprise Strategy');

        // Test linked co-author via many-to-many relationship
        $coAuthor = Author::create(['name' => 'Dr. Coauthor Contributor', 'slug' => 'dr-coauthor-contributor']);
        $this->book2->authors()->attach($coAuthor->id, ['role' => 'co_author']);

        $response3 = $this->get(route('explore', ['q' => 'Coauthor Contributor', 'search_by' => 'author']));
        $response3->assertOk();
        $response3->assertSee('Beta AI & Enterprise Strategy');
        $response3->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * 4. Test Subject search matches disciplines/categories and excludes title/author matches.
     */
    public function test_search_subject_matches_disciplines_and_categories(): void
    {
        // Category "Strategic Leadership" matches Book 1 and Book 3
        $response = $this->get(route('explore', ['q' => 'Strategic Leadership', 'search_by' => 'subject']));
        $response->assertOk();
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertSee('Gamma Institutional Policy Review');
        $response->assertDontSee('Beta AI & Enterprise Strategy');

        // Subject search with "Governance" (title word, not discipline) returns nothing
        $response2 = $this->get(route('explore', ['q' => 'Governance', 'search_by' => 'subject']));
        $response2->assertOk();
        $response2->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * 5. Test Tags search matches assigned tags and excludes title/author matches.
     */
    public function test_search_tags_matches_assigned_tags(): void
    {
        // Tag "machine-learning" is assigned to Book 2
        $response = $this->get(route('explore', ['q' => 'machine-learning', 'search_by' => 'tags']));
        $response->assertOk();
        $response->assertSee('Beta AI & Enterprise Strategy');
        $response->assertDontSee('Alpha Governance Frameworks');

        // Tag search with "Enterprise" (title word, not tag) returns nothing
        $response2 = $this->get(route('explore', ['q' => 'Enterprise', 'search_by' => 'tags']));
        $response2->assertOk();
        $response2->assertDontSee('Beta AI & Enterprise Strategy');
    }

    /**
     * 6. Test ISBN/DOI search finds correct identifiers with hyphen and URL normalization.
     */
    public function test_search_isbn_and_doi_finds_correct_identifiers_with_normalization(): void
    {
        // Hyphenated ISBN search matches Book 1 ('978-0-123456-78-9')
        $respHyphen = $this->get(route('explore', ['q' => '978-0-123456-78-9', 'search_by' => 'isbn_doi']));
        $respHyphen->assertOk();
        $respHyphen->assertSee('Alpha Governance Frameworks');
        $respHyphen->assertDontSee('Beta AI & Enterprise Strategy');

        // Unhyphenated ISBN search matches Book 1 ('9780123456789') via normalization
        $respClean = $this->get(route('explore', ['q' => '9780123456789', 'search_by' => 'isbn']));
        $respClean->assertOk();
        $respClean->assertSee('Alpha Governance Frameworks');
        $respClean->assertDontSee('Beta AI & Enterprise Strategy');

        // Exact DOI search matches Book 2 ('10.2000/beta.ai.2023')
        $respDoi = $this->get(route('explore', ['q' => '10.2000/beta.ai.2023', 'search_by' => 'isbn_doi']));
        $respDoi->assertOk();
        $respDoi->assertSee('Beta AI & Enterprise Strategy');
        $respDoi->assertDontSee('Alpha Governance Frameworks');

        // Full URL DOI search matches Book 2 via prefix normalization
        $respDoiUrl = $this->get(route('explore', ['q' => 'https://doi.org/10.2000/beta.ai.2023', 'search_by' => 'doi']));
        $respDoiUrl->assertOk();
        $respDoiUrl->assertSee('Beta AI & Enterprise Strategy');

        // Identifier search with "Governance" (title word, not in any ISBN/DOI) returns nothing
        $respNone = $this->get(route('explore', ['q' => 'Governance', 'search_by' => 'isbn_doi']));
        $respNone->assertOk();
        $respNone->assertDontSee('Alpha Governance Frameworks');
        $respNone->assertDontSee('Beta AI & Enterprise Strategy');
    }

    /**
     * 7. Test Publisher search matches assigned publisher.
     */
    public function test_search_publisher_matches_assigned_publisher(): void
    {
        $response = $this->get(route('explore', ['q' => 'Executive Press', 'search_by' => 'publisher']));
        $response->assertOk();
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertDontSee('Beta AI & Enterprise Strategy');

        // Non-publisher query returns nothing
        $responseNone = $this->get(route('explore', ['q' => 'Governance', 'search_by' => 'publisher']));
        $responseNone->assertOk();
        $responseNone->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * 8. Test Series search matches assigned series.
     */
    public function test_search_series_matches_assigned_series(): void
    {
        $response = $this->get(route('explore', ['q' => 'Artificial Intelligence Innovations', 'search_by' => 'series']));
        $response->assertOk();
        $response->assertSee('Beta AI & Enterprise Strategy');
        $response->assertDontSee('Alpha Governance Frameworks');

        // Non-series query returns nothing
        $responseNone = $this->get(route('explore', ['q' => 'Frameworks', 'search_by' => 'series']));
        $responseNone->assertOk();
        $responseNone->assertDontSee('Alpha Governance Frameworks');
    }

    /**
     * 9. Test field-specific search combined with facets and preserves library scope.
     */
    public function test_field_specific_search_combines_with_facets_and_preserves_scope(): void
    {
        // Author Vance + Library A => Book 1 only (Book 3 is in Library B)
        $respLib = $this->get(route('explore', [
            'q' => 'Vance',
            'search_by' => 'author',
            'library' => $this->libraryA->slug,
        ]));
        $respLib->assertOk();
        $respLib->assertSee('Alpha Governance Frameworks');
        $respLib->assertDontSee('Gamma Institutional Policy Review');

        // Author Vance + Type policy_document => Book 3 only
        $respType = $this->get(route('explore', [
            'q' => 'Vance',
            'search_by' => 'author',
            'type' => 'policy_document',
        ]));
        $respType->assertOk();
        $respType->assertSee('Gamma Institutional Policy Review');
        $respType->assertDontSee('Alpha Governance Frameworks');

        // Library show page: scoped search inside Library A preserves library scope
        $respShow = $this->get(route('libraries.show', [
            'slug' => $this->libraryA->slug,
            'q' => 'Vance',
            'search_by' => 'author',
        ]));
        $respShow->assertOk();
        $respShow->assertSee('Alpha Governance Frameworks');
        $respShow->assertDontSee('Gamma Institutional Policy Review');

        // Facet counts reflect active search field constraints
        $respJson = $this->getJson(route('explore', [
            'q' => 'Vance',
            'search_by' => 'author',
        ]));
        $respJson->assertOk();
        $this->assertEquals(2, $respJson->json('count'));
        $facetCounts = $respJson->json('facet_counts');
        $this->assertEquals(1, $facetCounts['types']['book'] ?? 0);
        $this->assertEquals(1, $facetCounts['types']['policy_document'] ?? 0);
        $this->assertEquals(0, $facetCounts['types']['research_paper'] ?? 0);
    }

    /**
     * 10. Test search field persists across sorting, pagination, and view modes.
     */
    public function test_search_field_persists_across_sorting_pagination_and_views(): void
    {
        // Create additional books by Dr. Alpha Vance to trigger pagination (>20 records)
        for ($i = 4; $i <= 25; $i++) {
            Book::create([
                'library_id' => $this->libraryA->id,
                'category_id' => $this->catLeadership->id,
                'author_id' => $this->authorAlpha->id,
                'title' => "Extra Leadership Volume {$i}",
                'slug' => "extra-leadership-volume-{$i}",
                'author_name' => $this->authorAlpha->name,
                'status' => 'published',
                'publication_type' => 'book',
                'publication_year' => 2026,
            ]);
        }

        $response = $this->get(route('explore', [
            'q' => 'Vance',
            'search_by' => 'author',
            'sort' => 'title_asc',
            'view' => 'list',
        ]));

        $response->assertOk();
        $response->assertSee('books-list-view');
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertSee('search_by=author');
        $response->assertSee('sort=title_asc');
        $response->assertSee('view=list');
        $response->assertSee('page=2');
    }

    /**
     * 11. Test search field AJAX and live fetching responses.
     */
    public function test_search_field_ajax_and_history_support(): void
    {
        $response = $this->getJson(route('explore', [
            'q' => 'Beta Montgomery',
            'search_by' => 'author',
            'view' => 'grid',
        ]));

        $response->assertOk();
        $response->assertJson([
            'count' => 1,
        ]);
        $this->assertStringContainsString('Beta AI &amp; Enterprise Strategy', $response->json('html'));
        $this->assertEquals(1, $response->json('facet_counts.total'));
    }

    /**
     * 12. Test invalid search field falls back safely to all fields.
     */
    public function test_invalid_search_field_falls_back_safely_to_all_fields(): void
    {
        // Malicious column attempt falls back to all fields
        $response = $this->get(route('explore', [
            'q' => 'Executive Press',
            'search_by' => 'non_existent_column_injection_attempt',
        ]));

        $response->assertOk();
        // Since it falls back to 'all', it matches Book 1 by publisher
        $response->assertSee('Alpha Governance Frameworks');
    }

    /**
     * 13. Test search field excludes draft and private library publications from public search.
     */
    public function test_search_field_excludes_draft_and_private_library_publications(): void
    {
        // Draft book with author Dr. Alpha Vance
        Book::create([
            'library_id' => $this->libraryA->id,
            'category_id' => $this->catLeadership->id,
            'author_id' => $this->authorAlpha->id,
            'title' => 'Alpha Classified Draft Governance',
            'slug' => 'alpha-classified-draft-governance',
            'author_name' => $this->authorAlpha->name,
            'status' => 'draft',
            'publication_type' => 'book',
            'publication_year' => 2026,
            'visibility' => 'public',
        ]);

        // Private library with author Dr. Alpha Vance
        $privateLib = Library::create([
            'name' => 'Internal High Security Archives',
            'slug' => 'internal-high-security-archives',
            'visibility' => 'private',
            'is_published' => true,
            'sort_order' => 99,
        ]);

        Book::create([
            'library_id' => $privateLib->id,
            'category_id' => $this->catLeadership->id,
            'author_id' => $this->authorAlpha->id,
            'title' => 'Internal Sovereign Dossier',
            'slug' => 'internal-sovereign-dossier',
            'author_name' => $this->authorAlpha->name,
            'status' => 'published',
            'publication_type' => 'book',
            'publication_year' => 2026,
            'visibility' => 'public',
        ]);

        $response = $this->get(route('explore', [
            'q' => 'Vance',
            'search_by' => 'author',
        ]));

        $response->assertOk();
        $response->assertSee('Alpha Governance Frameworks');
        $response->assertSee('Gamma Institutional Policy Review');
        $response->assertDontSee('Alpha Classified Draft Governance');
        $response->assertDontSee('Internal Sovereign Dossier');
    }

    /**
     * 14. Test catalogue UI renders combined search control with field selector options.
     */
    public function test_catalogue_ui_renders_combined_search_control_with_field_options(): void
    {
        $response = $this->get(route('explore'));

        $response->assertOk();
        $response->assertSee('catalogue-search-bar');
        $response->assertSee('catalogue-search-field-select');
        $response->assertSee('All Fields');
        $response->assertSee('Title');
        $response->assertSee('Author / Creator');
        $response->assertSee('Subject / Discipline');
        $response->assertSee('Tags / Keywords');
        $response->assertSee('ISBN / DOI');
        $response->assertSee('Publisher');
        $response->assertSee('Series');

        // Also rendered in library scoped show page
        $responseLib = $this->get(route('libraries.show', $this->libraryA->slug));
        $responseLib->assertOk();
        $responseLib->assertSee('catalogue-search-bar');
        $responseLib->assertSee('catalogue-search-field-select');
        $responseLib->assertSee('All Fields');
    }
}
