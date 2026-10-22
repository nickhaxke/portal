<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\Library;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BibliographicBookDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reader;

    protected Library $publicLibrary;

    protected Library $privateLibrary;

    protected Category $mainCategory;

    protected Category $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Dr. Eleanor Vance',
            'email' => 'admin@governance.portal',
            'password' => Hash::make('AdminPass2026!'),
            'role' => 'admin',
        ]);

        $this->reader = User::create([
            'name' => 'Marcus Holloway',
            'email' => 'm.holloway@oxford.edu',
            'password' => Hash::make('ReaderPass2026!'),
            'role' => 'reader',
            'institution' => 'Oxford University',
            'status' => 'active',
        ]);

        $this->publicLibrary = Library::create([
            'name' => 'Strategic Intelligence Repository',
            'slug' => 'strategic-intelligence',
            'description' => 'Flagship institutional digital library collection.',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $this->privateLibrary = Library::create([
            'name' => 'Restricted Defense Archives',
            'slug' => 'defense-archives',
            'description' => 'Classified executive defense intelligence.',
            'is_published' => true,
            'visibility' => 'private',
        ]);

        $this->mainCategory = Category::create([
            'name' => 'Corporate Governance',
            'slug' => 'corporate-governance',
            'sort_order' => 1,
        ]);

        $this->subCategory = Category::create([
            'parent_id' => $this->mainCategory->id,
            'name' => 'Executive Board Architecture',
            'slug' => 'board-architecture',
            'sort_order' => 2,
        ]);
    }

    /**
     * Test complete bibliographic record rendering with all metadata fields.
     */
    public function test_complete_bibliographic_record_renders_all_sections(): void
    {
        $author = Author::create([
            'name' => 'Prof. Katherine Sterling',
            'slug' => 'prof-katherine-sterling',
        ]);

        $book = Book::create([
            'library_id' => $this->publicLibrary->id,
            'category_id' => $this->mainCategory->id,
            'author_id' => $author->id,
            'title' => 'Sovereign Institutional Leadership in Volatile Eras',
            'subtitle' => 'A Comprehensive Treatise on Board Strategy and Decision Architecture',
            'slug' => 'sovereign-institutional-leadership',
            'author_name' => 'Prof. Katherine Sterling',
            'publisher' => 'Oxford Executive Academic Press',
            'publication_year' => 2026,
            'edition' => '2nd Global Edition',
            'language' => 'English',
            'isbn' => '0-19-853453-1',
            'isbn13' => '978-0-19-853453-2',
            'doi' => '10.1093/sovereign/2026.04',
            'series' => 'Governance Monograph Series',
            'series_number' => 4.5,
            'pages_count' => 384,
            'publication_type' => 'book',
            'description' => 'An authoritative investigation into executive board governance and institutional resilience amidst global systemic volatility.',
            'status' => 'published',
            'allow_download' => true,
            'visibility' => 'public',
        ]);

        $book->syncTags('Governance, Board Architecture, Sovereign Leadership');

        BookFile::create([
            'book_id' => $book->id,
            'file_path' => 'books/sample.pdf',
            'file_name' => 'sovereign-institutional-leadership.pdf',
            'file_size' => 14680064, // 14 MB
            'file_type' => 'pdf',
            'disk' => 'local',
            'page_count' => 384,
            'is_active' => true,
        ]);

        $response = $this->get(route('books.show', $book->slug));

        $response->assertOk();

        // 1. Book Overview assertions
        $response->assertSee('Sovereign Institutional Leadership in Volatile Eras');
        $response->assertSee('A Comprehensive Treatise on Board Strategy and Decision Architecture');
        $response->assertSee('Prof. Katherine Sterling');
        $response->assertSee('Book / Monograph');
        $response->assertSee('Corporate Governance');
        $response->assertSee('Strategic Intelligence Repository');
        $response->assertSee('Governance Monograph Series');
        $response->assertSee('Public Digital Access');

        // 2. Bibliographic Information assertions
        $response->assertSee('Oxford Executive Academic Press');
        $response->assertSee('2026');
        $response->assertSee('2nd Global Edition');
        $response->assertSee('English');
        $response->assertSee('0-19-853453-1');
        $response->assertSee('978-0-19-853453-2');
        $response->assertSee('10.1093/sovereign/2026.04');
        $response->assertSee('384 pages');
        $response->assertSee('PDF');

        // 3. Description / Abstract
        $response->assertSee('An authoritative investigation into executive board governance');

        // 4. Subjects & Keywords
        $response->assertSee('Corporate Governance');
        $response->assertSee('#Governance');
        $response->assertSee('#Board Architecture');
        $response->assertSee('#Sovereign Leadership');

        // 5. Access & Reading
        $response->assertSee('Online Document Access Permitted');
        $response->assertSee('Read PDF Online &rarr;', false);
        $response->assertSee('Save to My Library');
        $response->assertSee('Bibliographic Citation (APA/Executive Standard)');
        $response->assertSee('Prof. Katherine Sterling (2026)');

        // 6. Interactive Links
        $response->assertSee(route('explore', ['q' => 'Prof. Katherine Sterling', 'search_by' => 'author']));
        $response->assertSee(route('explore', ['category' => 'corporate-governance']));
        $response->assertSee(route('explore', ['q' => 'Oxford Executive Academic Press', 'search_by' => 'publisher']));
        $response->assertSee(route('explore', ['q' => 'Governance Monograph Series', 'search_by' => 'series']));
        $response->assertSee(route('explore', ['q' => 'Governance', 'search_by' => 'tags']));
        $response->assertSee('https://doi.org/10.1093/sovereign/2026.04');
    }

    /**
     * Test partial metadata rendering: handles missing fields without fabricating data or crashing.
     */
    public function test_partial_metadata_renders_cleanly_without_dummy_fabrication(): void
    {
        $book = Book::create([
            'title' => 'Minimal Record Monograph',
            'slug' => 'minimal-record-monograph',
            'author_name' => 'Institutional Research Division',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $response = $this->get(route('books.show', $book->slug));

        $response->assertOk();
        $response->assertSee('Minimal Record Monograph');
        $response->assertSee('Institutional Research Division');

        // Should not render "N/A" or "Recent" or missing labels in Bibliographic table
        $response->assertDontSee('<strong>N/A</strong>', false);
        $response->assertDontSee('Recent');
        $response->assertDontSee('ISBN-10');
        $response->assertDontSee('ISBN-13');
        $response->assertDontSee('Digital Object Identifier (DOI)');

        // Should display empty state notice for abstract
        $response->assertSee('No formal abstract or executive summary has been catalogued');
    }

    /**
     * Test multi-author and contributor roles presentation.
     */
    public function test_multiple_authors_and_contributor_roles_display_properly(): void
    {
        $author1 = Author::create([
            'name' => 'Dr. Alistair Finch',
            'slug' => 'dr-alistair-finch',
        ]);

        $author2 = Author::create([
            'name' => 'Dame Victoria Wood',
            'slug' => 'dame-victoria-wood',
        ]);

        $author3 = Author::create([
            'name' => 'Prof. Kenji Takahashi',
            'slug' => 'prof-kenji-takahashi',
        ]);

        $book = Book::create([
            'title' => 'Comparative Sovereign Wealth Fund Strategies',
            'slug' => 'comparative-sovereign-wealth-fund-strategies',
            'author_name' => 'Dr. Alistair Finch et al.',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        // Attach multi-authors with roles
        $book->authors()->attach($author1->id, ['role' => 'lead_author', 'sort_order' => 1]);
        $book->authors()->attach($author2->id, ['role' => 'co_author', 'sort_order' => 2]);
        $book->authors()->attach($author3->id, ['role' => 'editor', 'sort_order' => 3]);

        $response = $this->get(route('books.show', $book->slug));

        $response->assertOk();
        $response->assertSee('Dr. Alistair Finch');
        $response->assertSee('Lead Author');
        $response->assertSee('Dame Victoria Wood');
        $response->assertSee('Co Author');
        $response->assertSee('Prof. Kenji Takahashi');
        $response->assertSee('Editor');

        $response->assertSee(route('explore', ['q' => 'Dr. Alistair Finch', 'search_by' => 'author']));
        $response->assertSee(route('explore', ['q' => 'Dame Victoria Wood', 'search_by' => 'author']));
        $response->assertSee(route('explore', ['q' => 'Prof. Kenji Takahashi', 'search_by' => 'author']));
    }

    /**
     * Test multiple subjects and categories.
     */
    public function test_multi_classification_subjects_display(): void
    {
        $book = Book::create([
            'title' => 'Cross-Disciplinary Risk Management',
            'slug' => 'cross-disciplinary-risk-management',
            'author_name' => 'Risk Governance Council',
            'category_id' => $this->mainCategory->id,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        // Attach secondary discipline
        $book->categories()->attach($this->subCategory->id, ['is_primary' => false]);

        $response = $this->get(route('books.show', $book->slug));

        $response->assertOk();
        $response->assertSee('Corporate Governance');
        $response->assertSee('Executive Board Architecture');
        $response->assertSee(route('explore', ['category' => 'corporate-governance']));
        $response->assertSee(route('explore', ['category' => 'board-architecture']));
    }

    /**
     * Test reader access journey: restricted publication requires authentication to read.
     */
    public function test_restricted_publication_requires_authentication(): void
    {
        $restrictedBook = Book::create([
            'title' => 'Restricted Executive Board Minutes',
            'slug' => 'restricted-executive-board-minutes',
            'author_name' => 'Board Secretariat',
            'status' => 'published',
            'visibility' => 'readers_only',
            'allow_download' => true,
        ]);

        // Guest visitor can view bibliographic metadata but sees sign-in prompt
        $guestResponse = $this->get(route('books.show', $restrictedBook->slug));
        $guestResponse->assertOk();
        $guestResponse->assertSee('Restricted Executive Board Minutes');
        $guestResponse->assertSee('Institutional Readers Only');
        $guestResponse->assertSee('Institutional Reader Authentication Required');
        $guestResponse->assertSee('Sign In to Read &rarr;', false);
        $guestResponse->assertSee('Sign in to download');

        // Authenticated reader can read online and download if allowed
        $readerResponse = $this->actingAs($this->reader)->get(route('books.show', $restrictedBook->slug));
        $readerResponse->assertOk();
        $readerResponse->assertSee('Online Document Access Permitted');
        $readerResponse->assertSee('Read PDF Online &rarr;', false);
        $readerResponse->assertSee('Download Document (PDF) ↗');
    }

    /**
     * Test download policy: online reading only when allow_download is false.
     */
    public function test_download_policy_enforcement_when_disallowed(): void
    {
        $onlineOnlyBook = Book::create([
            'title' => 'Strict Institutional Archive Volume 1',
            'slug' => 'strict-institutional-archive-vol-1',
            'author_name' => 'Archive Preservation Committee',
            'status' => 'published',
            'visibility' => 'public',
            'allow_download' => false,
        ]);

        $response = $this->actingAs($this->reader)->get(route('books.show', $onlineOnlyBook->slug));

        $response->assertOk();
        $response->assertSee('Online Reading Only');
        $response->assertSee('Institutional preservation policy restricts local reproduction');
        $response->assertDontSee('Download Document (PDF) ↗');
    }

    /**
     * Test reading progress restoration: displays resume banner and current position.
     */
    public function test_reading_progress_restoration_for_authenticated_reader(): void
    {
        $book = Book::create([
            'title' => 'Advanced Macroprudential Policy',
            'slug' => 'advanced-macroprudential-policy',
            'author_name' => 'Central Banking Research Guild',
            'pages_count' => 120,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        ReadingProgress::create([
            'user_id' => $this->reader->id,
            'book_id' => $book->id,
            'current_page' => 48,
            'total_pages' => 120,
            'percent_complete' => 40.00,
            'is_completed' => false,
            'last_read_at' => now(),
        ]);

        $response = $this->actingAs($this->reader)->get(route('books.show', $book->slug));

        $response->assertOk();
        $response->assertSee('Continue Reading from Saved Progress');
        $response->assertSee('Page 48 of 120 (40% completed)');
        $response->assertSee('Resume (Page 48) &rarr;', false);
    }

    /**
     * Test reading progress restoration for guest via session.
     */
    public function test_reading_progress_restoration_for_guest_reader(): void
    {
        $book = Book::create([
            'title' => 'Guest Session Reading Book',
            'slug' => 'guest-session-reading-book',
            'author_name' => 'Guest Contributor',
            'pages_count' => 80,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $response = $this->withSession([
            "reading_progress_{$book->id}" => [
                'current_page' => 20,
                'total_pages' => 80,
            ],
        ])->get(route('books.show', $book->slug));

        $response->assertOk();
        $response->assertSee('Continue Reading from Saved Progress');
        $response->assertSee('Page 20 of 80 (25% completed)');
    }

    /**
     * Test Save to My Library toggle action.
     */
    public function test_toggle_save_adds_and_removes_book_from_personal_library(): void
    {
        $book = Book::create([
            'title' => 'Personal Library Bookmark Book',
            'slug' => 'personal-library-bookmark-book',
            'author_name' => 'Library Archivist',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        // 1. Initial save request
        $saveResponse = $this->actingAs($this->reader)->postJson(route('books.toggle-save', $book->id));
        $saveResponse->assertOk();
        $saveResponse->assertJson(['saved' => true]);

        $this->assertDatabaseHas('saved_books', [
            'book_id' => $book->id,
            'user_id' => $this->reader->id,
        ]);

        // View shows saved state
        $pageResponse = $this->actingAs($this->reader)->get(route('books.show', $book->slug));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Saved to My Library');

        // 2. Toggle off
        $unsaveResponse = $this->actingAs($this->reader)->postJson(route('books.toggle-save', $book->id));
        $unsaveResponse->assertOk();
        $unsaveResponse->assertJson(['saved' => false]);

        $this->assertDatabaseMissing('saved_books', [
            'book_id' => $book->id,
            'user_id' => $this->reader->id,
        ]);
    }

    /**
     * Test Related Publications queries real metadata relationships and respects library privacy.
     */
    public function test_related_publications_uses_real_metadata_and_excludes_private_libraries(): void
    {
        $primaryBook = Book::create([
            'title' => 'Central Banking Frameworks',
            'slug' => 'central-banking-frameworks',
            'author_name' => 'Monetary Studies Institute',
            'category_id' => $this->mainCategory->id,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $relatedPublicBook = Book::create([
            'title' => 'Modern Monetary Architecture',
            'slug' => 'modern-monetary-architecture',
            'author_name' => 'Monetary Studies Institute',
            'category_id' => $this->mainCategory->id,
            'library_id' => $this->publicLibrary->id,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $relatedPrivateBook = Book::create([
            'title' => 'Classified Sovereign Liquidity Reserves',
            'slug' => 'classified-sovereign-liquidity-reserves',
            'author_name' => 'Defense Intelligence Taskforce',
            'category_id' => $this->mainCategory->id,
            'library_id' => $this->privateLibrary->id, // Private library!
            'status' => 'published',
            'visibility' => 'public',
        ]);

        // Unauthenticated visitor
        $visitorResponse = $this->get(route('books.show', $primaryBook->slug));
        $visitorResponse->assertOk();
        $visitorResponse->assertSee('Related Publications');
        $visitorResponse->assertSee('Modern Monetary Architecture');
        // Private library book must NOT appear in related recommendations
        $visitorResponse->assertDontSee('Classified Sovereign Liquidity Reserves');

        // Admin can see related private books
        $adminResponse = $this->actingAs($this->admin)->get(route('books.show', $primaryBook->slug));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Classified Sovereign Liquidity Reserves');
    }

    /**
     * Test Cover Integrity: correct cover association, replacement, missing fallback, and isolation.
     */
    public function test_cover_integrity_association_fallback_and_isolation(): void
    {
        // 1. Correct cover per book
        $bookAlpha = Book::create([
            'title' => 'Leadership Architecture Alpha',
            'slug' => 'leadership-architecture-alpha',
            'author_name' => 'Dr. Alpha Vance',
            'cover_image' => 'covers/cover_alpha_unique_123.jpg',
            'cover_bg' => '#102946',
            'cover_fg' => '#ffffff',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $bookBeta = Book::create([
            'title' => 'Strategic Foresight Beta',
            'slug' => 'strategic-foresight-beta',
            'author_name' => 'Prof. Beta Thorne',
            'cover_image' => 'covers/cover_beta_unique_456.jpg',
            'cover_bg' => '#1b3b22',
            'cover_fg' => '#fefefe',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $alphaResponse = $this->get(route('books.show', $bookAlpha->slug));
        $alphaResponse->assertOk();
        $alphaResponse->assertSee('cover_alpha_unique_123.jpg');
        $alphaResponse->assertDontSee('cover_beta_unique_456.jpg');

        $betaResponse = $this->get(route('books.show', $bookBeta->slug));
        $betaResponse->assertOk();
        $betaResponse->assertSee('cover_beta_unique_456.jpg');
        $betaResponse->assertDontSee('cover_alpha_unique_123.jpg');

        // 2. Cover replacement
        $bookAlpha->update(['cover_image' => 'covers/cover_alpha_v2_789.jpg']);
        $replacedResponse = $this->get(route('books.show', $bookAlpha->slug));
        $replacedResponse->assertOk();
        $replacedResponse->assertSee('cover_alpha_v2_789.jpg');
        $replacedResponse->assertDontSee('cover_alpha_unique_123.jpg');

        // 3. Missing cover fallback (renders monogram typographical cover)
        $bookFallback = Book::create([
            'title' => 'Uncovered Policy Brief',
            'slug' => 'uncovered-policy-brief',
            'author_name' => 'Foresight Panel',
            'cover_image' => null,
            'cover_bg' => '#334155',
            'cover_fg' => '#ffffff',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $fallbackResponse = $this->get(route('books.show', $bookFallback->slug));
        $fallbackResponse->assertOk();
        $fallbackResponse->assertSee('Uncovered Policy Brief');
        $fallbackResponse->assertSee('EXECUTIVE E-LIBRARY · CURATED');
        $fallbackResponse->assertSee('#334155'); // Stylized monogram cover background
        $fallbackResponse->assertDontSee('<img src=""', false);

        // 4. Calibre imported cover mapping
        $calibreBook = Book::create([
            'title' => 'Calibre Imported Monograph',
            'slug' => 'calibre-imported-monograph',
            'author_name' => 'Historian Board',
            'calibre_id' => 42,
            'cover_image' => 'covers/calibre_42_imported_monograph.jpg',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $calibreResponse = $this->get(route('books.show', $calibreBook->slug));
        $calibreResponse->assertOk();
        $calibreResponse->assertSee('calibre_42_imported_monograph.jpg');
    }
}
