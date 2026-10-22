<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookBookmark;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalP1ReaderAndPublicationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reader;

    protected Category $parentCategory;

    protected Category $subCategory;

    protected Book $researchPaper;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        // Create Admin
        $this->admin = User::create([
            'name' => 'Executive Director',
            'email' => 'admin@executive.portal',
            'password' => Hash::make('Password123!'),
            'role' => 'admin',
            'position_title' => 'Chief Executive & Board Administrator',
        ]);

        // Create Reader
        $this->reader = User::create([
            'name' => 'Dr. Robert Harrison',
            'email' => 'r.harrison@governance.org',
            'password' => Hash::make('ReaderPass123!'),
            'role' => 'reader',
            'position_title' => 'Distinguished Senior Fellow',
            'institution' => 'Global Governance Institute',
            'status' => 'active',
        ]);

        // Create Hierarchical Categories
        $this->parentCategory = Category::create([
            'name' => 'Strategic Governance',
            'slug' => 'strategic-governance',
            'icon' => '🏛',
            'description' => 'Board governance and institutional leadership.',
            'sort_order' => 1,
        ]);

        $this->subCategory = Category::create([
            'parent_id' => $this->parentCategory->id,
            'name' => 'Board Decision Architecture',
            'slug' => 'board-decision-architecture',
            'icon' => '♟',
            'description' => 'Advanced high-stakes decision frameworks for boards.',
            'sort_order' => 2,
        ]);

        // Create a Research Paper with Bibliographic Metadata
        $this->researchPaper = Book::create([
            'title' => 'Artificial Intelligence in Corporate Boardrooms',
            'subtitle' => 'Empirical Analysis of Algorithmic Risk Governance',
            'slug' => 'ai-corporate-boardrooms-risk-governance',
            'publication_type' => 'research_paper',
            'category_id' => $this->subCategory->id,
            'author_name' => 'Dr. Elizabeth Warren-Cross',
            'publisher' => 'Oxford Executive Review',
            'edition' => '2026 Monographs',
            'publication_year' => 2026,
            'pages_count' => 84,
            'doi' => '10.1016/j.exec.2026.04.012',
            'isbn' => '978-0-19-887654-1',
            'description' => 'Comprehensive empirical study on AI oversight in board governance.',
            'status' => 'published',
            'allow_download' => true,
        ]);

        $fakePdf = UploadedFile::fake()->create('ai-boardrooms.pdf', 800, 'application/pdf');
        $storedPath = $fakePdf->store('books/pdfs', 'local');
        BookFile::create([
            'book_id' => $this->researchPaper->id,
            'file_path' => $storedPath,
            'file_name' => 'ai-boardrooms.pdf',
            'file_type' => 'pdf',
            'file_size' => 800 * 1024,
            'disk' => 'local',
        ]);
    }

    /**
     * Test P1: Reader Registration creates active reader account and redirects to library.
     */
    public function test_reader_registration_creates_account_and_logs_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Prof. Marcus Vance',
            'email' => 'm.vance@oxford-executive.org',
            'position_title' => 'Chair of Strategic Management',
            'institution' => 'Oxford Institute for Leadership',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
        ]);

        $response->assertRedirect('/my-library');

        $this->assertDatabaseHas('users', [
            'email' => 'm.vance@oxford-executive.org',
            'role' => 'reader',
            'position_title' => 'Chair of Strategic Management',
            'institution' => 'Oxford Institute for Leadership',
            'status' => 'active',
        ]);

        $user = User::where('email', 'm.vance@oxford-executive.org')->first();
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test P1: Registration enforces institutional domain when policy is active.
     */
    public function test_registration_enforces_domain_restriction_policy(): void
    {
        Config::set('portal.registration_policy', 'institutional_domain');
        Config::set('portal.allowed_domains', ['governance.org', 'oxford.edu']);

        // Attempt registration with unauthorized domain
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'position_title' => 'Independent Consultant',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'john.doe@gmail.com']);

        // Attempt registration with authorized domain
        $validResponse = $this->post('/register', [
            'name' => 'Authorized Fellow',
            'email' => 'fellow@governance.org',
            'position_title' => 'Research Fellow',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $validResponse->assertRedirect('/my-library');
        $this->assertDatabaseHas('users', ['email' => 'fellow@governance.org']);
    }

    /**
     * Test P1: Password reset request generates link.
     */
    public function test_password_recovery_request(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => $this->reader->email,
        ]);

        $response->assertSessionHas('status');
    }

    /**
     * Test P1: Publication Types and Bibliographic Metadata Formatting.
     */
    public function test_publication_types_and_citation_formatting(): void
    {
        $this->assertEquals('Research Paper', $this->researchPaper->formattedPublicationType());

        $citation = $this->researchPaper->formattedCitation();
        $this->assertStringContainsString('Dr. Elizabeth Warren-Cross', $citation);
        $this->assertStringContainsString('2026', $citation);
        $this->assertStringContainsString('Artificial Intelligence in Corporate Boardrooms', $citation);
        $this->assertStringContainsString('Oxford Executive Review', $citation);
        $this->assertStringContainsString('https://doi.org/10.1016/j.exec.2026.04.012', $citation);

        // Test scopeOfType
        $this->assertCount(1, Book::ofType('research_paper')->get());
        $this->assertCount(0, Book::ofType('thesis')->get());
    }

    /**
     * Test P1: Hierarchical Category Relationships.
     */
    public function test_hierarchical_category_parent_and_children(): void
    {
        $this->assertTrue($this->parentCategory->children->contains($this->subCategory));
        $this->assertEquals($this->parentCategory->id, $this->subCategory->parent->id);
        $this->assertCount(1, Category::root()->get());
    }

    /**
     * Test P1: Reading progress beacon updates percent and position.
     */
    public function test_reading_progress_beacon_records_progress(): void
    {
        $this->actingAs($this->reader);

        $response = $this->postJson(route('books.progress', $this->researchPaper->slug), [
            'current_page' => 21,
            'total_pages' => 84,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'current_page' => 21,
            'percent_complete' => 25.00,
            'is_completed' => false,
        ]);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $this->reader->id,
            'book_id' => $this->researchPaper->id,
            'current_page' => 21,
            'total_pages' => 84,
            'percent_complete' => 25.00,
            'is_completed' => false,
        ]);
    }

    /**
     * Test P1: Reader view automatically restores initial page from reading progress.
     */
    public function test_reader_restores_initial_page_from_saved_progress(): void
    {
        $this->actingAs($this->reader);

        ReadingProgress::create([
            'user_id' => $this->reader->id,
            'book_id' => $this->researchPaper->id,
            'current_page' => 42,
            'total_pages' => 84,
            'percent_complete' => 50.00,
            'is_completed' => false,
            'last_read_at' => now(),
        ]);

        $response = $this->get(route('books.reader', $this->researchPaper->slug));
        $response->assertStatus(200);
        $response->assertSee('pageNum = 42', false);
        $response->assertSee('Resumed from your saved progress (Page 42)');
    }

    /**
     * Test P1: Continue Reading shelf displays on /my-library.
     */
    public function test_continue_reading_shelf_displays_in_reader_library(): void
    {
        $this->actingAs($this->reader);

        ReadingProgress::create([
            'user_id' => $this->reader->id,
            'book_id' => $this->researchPaper->id,
            'current_page' => 42,
            'total_pages' => 84,
            'percent_complete' => 50.00,
            'is_completed' => false,
            'last_read_at' => now(),
        ]);

        $response = $this->get('/my-library');
        $response->assertStatus(200);
        $response->assertSee('Continue Reading');
        $response->assertSee('Artificial Intelligence in Corporate Boardrooms');
        $response->assertSee('Page 42 of 84');
        $response->assertSee('50%');
    }

    /**
     * Test P1: Reader bookmark creation and deletion.
     */
    public function test_reader_bookmark_creation_and_deletion(): void
    {
        $this->actingAs($this->reader);

        // Create bookmark on page 28
        $createResponse = $this->postJson(route('books.bookmark', $this->researchPaper->slug), [
            'page_number' => 28,
            'title' => 'Key Framework Table 3.2',
        ]);

        $createResponse->assertStatus(200);
        $createResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('book_bookmarks', [
            'user_id' => $this->reader->id,
            'book_id' => $this->researchPaper->id,
            'page_number' => 28,
            'title' => 'Key Framework Table 3.2',
        ]);

        $bookmark = BookBookmark::where('book_id', $this->researchPaper->id)->first();

        // Delete bookmark
        $deleteResponse = $this->deleteJson(route('books.bookmark.remove', [
            'slug' => $this->researchPaper->slug,
            'bookmark' => $bookmark->id,
        ]));

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('book_bookmarks', ['id' => $bookmark->id]);
    }

    /**
     * Test P1: Admin Book Creation with Bibliographic Metadata and Publication Types.
     */
    public function test_admin_can_create_publication_with_bibliographic_metadata(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.books.store'), [
            'title' => 'Global Macroeconomic Policy Brief 2026',
            'subtitle' => 'Executive Analysis of Sovereign Debt',
            'publication_type' => 'policy_document',
            'author_name' => 'World Economic Advisory Board',
            'category_id' => $this->parentCategory->id,
            'publisher' => 'International Governance Council',
            'edition' => 'Q4 Edition',
            'publication_year' => 2026,
            'pages_count' => 64,
            'doi' => '10.1093/policy/2026.01',
            'isbn' => '978-1-5261-0000-0',
            'status' => 'published',
            'allow_download' => true,
        ]);

        $response->assertRedirect(route('admin.books.index'));

        $this->assertDatabaseHas('books', [
            'title' => 'Global Macroeconomic Policy Brief 2026',
            'subtitle' => 'Executive Analysis of Sovereign Debt',
            'publication_type' => 'policy_document',
            'publisher' => 'International Governance Council',
            'doi' => '10.1093/policy/2026.01',
            'status' => 'published',
        ]);
    }
}
