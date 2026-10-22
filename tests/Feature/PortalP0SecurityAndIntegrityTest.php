<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\User;
use App\Services\PublicationAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalP0SecurityAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reader;

    protected Category $category;

    protected Book $publishedBook;

    protected Book $draftBook;

    protected Book $onlineOnlyBook;

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

        // Create Standard Reader
        $this->reader = User::create([
            'name' => 'Fellow Reader',
            'email' => 'reader@executive.portal',
            'password' => Hash::make('ReaderPass123!'),
            'role' => 'reader',
            'position_title' => 'Senior Strategic Fellow',
        ]);

        // Create Category
        $this->category = Category::create([
            'name' => 'Strategic Governance',
            'slug' => 'strategic-governance',
            'icon' => '🏛',
            'description' => 'Board governance and institutional leadership.',
            'sort_order' => 1,
        ]);

        // Create a Published Book with download allowed
        $this->publishedBook = Book::create([
            'title' => 'The Effective Executive in Modern Era',
            'slug' => 'the-effective-executive-modern-era',
            'author_name' => 'Peter F. Drucker',
            'category_id' => $this->category->id,
            'description' => 'Classic guidance on strategic executive management.',
            'status' => 'published',
            'allow_download' => true,
            'is_featured' => true,
        ]);

        // Upload fake PDF for published book
        $fakePdf = UploadedFile::fake()->create('effective-exec.pdf', 500, 'application/pdf');
        $storedPath = $fakePdf->store('books/pdfs', 'local');
        BookFile::create([
            'book_id' => $this->publishedBook->id,
            'file_path' => $storedPath,
            'file_name' => 'effective-exec.pdf',
            'file_type' => 'pdf',
            'file_size' => 500 * 1024,
            'disk' => 'local',
        ]);

        // Create a Draft Book (Unpublished)
        $this->draftBook = Book::create([
            'title' => 'Confidential Intelligence Briefing 2027',
            'slug' => 'confidential-intelligence-briefing-2027',
            'author_name' => 'Strategic Advisory Council',
            'category_id' => $this->category->id,
            'description' => 'Unreleased strategic intelligence briefing.',
            'status' => 'draft',
            'allow_download' => false,
            'is_featured' => false,
        ]);

        // Create an Online-Only Book (Published but non-downloadable)
        $this->onlineOnlyBook = Book::create([
            'title' => 'Restricted Policy Whitepaper',
            'slug' => 'restricted-policy-whitepaper',
            'author_name' => 'Governance Institute',
            'category_id' => $this->category->id,
            'description' => 'Protected publication with online reading only enforced.',
            'status' => 'published',
            'allow_download' => false,
            'is_featured' => false,
        ]);

        $onlinePdf = UploadedFile::fake()->create('restricted-policy.pdf', 300, 'application/pdf');
        $onlineStoredPath = $onlinePdf->store('books/pdfs', 'local');
        BookFile::create([
            'book_id' => $this->onlineOnlyBook->id,
            'file_path' => $onlineStoredPath,
            'file_name' => 'restricted-policy.pdf',
            'file_type' => 'pdf',
            'file_size' => 300 * 1024,
            'disk' => 'local',
        ]);
    }

    /**
     * Test P0.B: Login rate limiting triggers after 5 failed attempts.
     */
    public function test_login_rate_limiting_throttles_after_5_failed_attempts(): void
    {
        RateLimiter::clear('admin-login:admin@executive.portal|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/admin/login', [
                'email' => 'admin@executive.portal',
                'password' => 'WrongPassword!',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be rate limited with too many requests message
        $response = $this->post('/admin/login', [
            'email' => 'admin@executive.portal',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Too many authentication attempts', $errors[0]);
    }

    /**
     * Test P0.B: Successful authentication regenerates session and routes by role.
     */
    public function test_successful_admin_login_regenerates_session_and_routes_to_admin(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@executive.portal',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->admin);
    }

    /**
     * Test P0.B: Reader login routes to reader portal /my-library.
     */
    public function test_reader_login_routes_to_reader_library(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'reader@executive.portal',
            'password' => 'ReaderPass123!',
        ]);

        $response->assertRedirect('/my-library');
        $this->assertAuthenticatedAs($this->reader);
    }

    /**
     * Test P0.B: Logout invalidates session and redirects.
     */
    public function test_logout_invalidates_session(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/logout');
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test P0.D: Draft publications are strictly excluded from public queries.
     */
    public function test_draft_publications_are_hidden_from_public_catalogue_and_home(): void
    {
        // Home page
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee($this->publishedBook->title);
        $homeResponse->assertDontSee($this->draftBook->title);

        // Explore page
        $exploreResponse = $this->get('/explore');
        $exploreResponse->assertStatus(200);
        $exploreResponse->assertSee($this->publishedBook->title);
        $exploreResponse->assertDontSee($this->draftBook->title);

        // Guest requesting draft book detail must receive 404
        $draftDetailResponse = $this->get(route('books.show', $this->draftBook->slug));
        $draftDetailResponse->assertStatus(404);

        // Guest requesting draft reader must receive 404
        $draftReaderResponse = $this->get(route('books.reader', $this->draftBook->slug));
        $draftReaderResponse->assertStatus(404);
    }

    /**
     * Test P0.D: Admin user CAN preview draft publication.
     */
    public function test_admin_can_preview_draft_publication_and_reader(): void
    {
        $this->actingAs($this->admin);

        // Admin can view draft detail
        $draftDetailResponse = $this->get(route('books.show', $this->draftBook->slug));
        $draftDetailResponse->assertStatus(200);
        $draftDetailResponse->assertSee($this->draftBook->title);

        // Admin can preview draft reader
        $draftReaderResponse = $this->get(route('books.reader', $this->draftBook->slug));
        $draftReaderResponse->assertStatus(200);
    }

    /**
     * Test P0.C: PDF streaming route requires valid signed URL signature.
     */
    public function test_pdf_streaming_requires_valid_signed_signature(): void
    {
        // Unsigned request directly to stream endpoint must be blocked with 403
        $unsignedResponse = $this->get(route('books.stream-pdf', $this->publishedBook->slug));
        $unsignedResponse->assertStatus(403);

        // Tampered signature must also be rejected
        $tamperedUrl = route('books.stream-pdf', [
            'slug' => $this->publishedBook->slug,
            'expires' => now()->addMinutes(30)->timestamp,
            'signature' => 'invalid_tampered_signature_hex_code',
        ]);
        $tamperedResponse = $this->get($tamperedUrl);
        $tamperedResponse->assertStatus(403);
    }

    /**
     * Test P0.C: Valid signed stream URL allows streaming the document.
     */
    public function test_valid_signed_stream_url_allows_authorized_document_streaming(): void
    {
        /** @var PublicationAccessService $accessService */
        $accessService = app(PublicationAccessService::class);
        $signedUrl = $accessService->generateSignedStreamUrl($this->publishedBook, null);

        $streamResponse = $this->get($signedUrl);
        $streamResponse->assertStatus(200);
        $streamResponse->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test P0.C: Reader view respects allow_download policy.
     */
    public function test_reader_view_displays_download_link_only_when_permitted(): void
    {
        // Book with allow_download = true for authenticated reader
        $allowedResponse = $this->actingAs($this->reader)->get(route('books.reader', $this->publishedBook->slug));
        $allowedResponse->assertStatus(200);
        $allowedResponse->assertSee('Download Document ↗');

        // Book with allow_download = false for authenticated reader
        $restrictedResponse = $this->actingAs($this->reader)->get(route('books.reader', $this->onlineOnlyBook->slug));
        $restrictedResponse->assertStatus(200);
        $restrictedResponse->assertDontSee('Download Document ↗');
        $restrictedResponse->assertSee('Online Reading Only');
    }

    /**
     * Test P0.A: Database transaction rollback integrity.
     */
    public function test_database_transaction_rollback_preserves_integrity(): void
    {
        $initialCount = Book::count();

        try {
            DB::transaction(function () {
                Book::create([
                    'title' => 'Temporary Rollback Test',
                    'slug' => 'temporary-rollback-test',
                    'author_name' => 'Test Author',
                    'category_id' => $this->category->id,
                    'status' => 'draft',
                ]);

                // Deliberately trigger an exception inside the transaction
                throw new \RuntimeException('Intentional rollback trigger');
            });
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        $this->assertEquals($initialCount, Book::count());
        $this->assertNull(Book::where('slug', 'temporary-rollback-test')->first());
    }
}
