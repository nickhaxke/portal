<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\User;
use App\Services\PublicationAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExecutivePdfPerformanceAndCoverTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test@executive.portal'],
            [
                'name' => 'Admin Tester',
                'password' => bcrypt('Password123!'),
                'role' => 'admin',
            ]
        );

        $this->category = Category::firstOrCreate(
            ['slug' => 'test-governance'],
            ['name' => 'Test Governance', 'icon' => 'scale', 'sort_order' => 1]
        );

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * 1. Test 6MB PDF and cover upload completes, persists, and queues background processing.
     */
    public function test_6mb_pdf_upload_persists_and_returns_clean_response(): void
    {
        $dummyContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n".
                        "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n".
                        "3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj\n".
                        '% '.str_repeat("0123456789ABCDEF\n% ", 380000)."\nxref\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        $pdf = UploadedFile::fake()->createWithContent('strategic_masterclass_6mb.pdf', $dummyContent);
        $cover = UploadedFile::fake()->image('custom_cover.jpg', 600, 900);

        $response = $this->actingAs($this->admin)->postJson(route('admin.books.store'), [
            'title' => 'Strategic Governance At Scale',
            'author_name' => 'Dr. Elena Rostova',
            'category_id' => $this->category->id,
            'publication_type' => 'book',
            'status' => 'published',
            'visibility' => 'public',
            'allow_download' => '1',
            'pdf_file' => $pdf,
            'cover_image' => $cover,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $book = Book::where('title', 'Strategic Governance At Scale')->first();
        $this->assertNotNull($book);
        $this->assertNotNull($book->cover_image);
        $this->assertStringContainsString('covers/', $book->cover_image);

        $primaryPdf = $book->primaryPdf;
        $this->assertNotNull($primaryPdf);
        $this->assertTrue(Storage::disk('local')->exists($primaryPdf->file_path));
    }

    /**
     * 2. Test PDF Reader streaming supports HTTP 206 Range requests and ETags.
     */
    public function test_reader_stream_supports_http_byte_ranges_and_etag(): void
    {
        $book = Book::firstOrCreate(
            ['slug' => 'stream-range-test-book'],
            [
                'title' => 'Range Stream Test Publication',
                'author_name' => 'Board Fellow',
                'category_id' => $this->category->id,
                'status' => 'published',
                'visibility' => 'public',
                'pages_count' => 5,
            ]
        );

        $pdfBinary = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n".
                     "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n".
                     "3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj\n".
                     '% '.str_repeat('A', 10000)."\nxref\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        $storedPath = 'private/books/stream-range-test.pdf';
        Storage::disk('local')->put($storedPath, $pdfBinary);

        BookFile::updateOrCreate(
            ['book_id' => $book->id, 'file_type' => 'pdf'],
            [
                'file_path' => $storedPath,
                'file_name' => 'stream-range-test.pdf',
                'file_size' => strlen($pdfBinary),
                'file_hash' => hash('sha256', $pdfBinary),
                'disk' => 'local',
                'page_count' => 1,
                'is_active' => true,
            ]
        );

        $accessService = app(PublicationAccessService::class);
        $signedUrl = $accessService->generateSignedStreamUrl($book);

        // Test Range request: bytes 0-511
        $rangeResponse = $this->get($signedUrl, [
            'Range' => 'bytes=0-511',
        ]);

        $rangeResponse->assertStatus(206);
        $rangeResponse->assertHeader('Accept-Ranges', 'bytes');
        $rangeResponse->assertHeader('Content-Range', 'bytes 0-511/'.strlen($pdfBinary));
        $this->assertEquals(512, strlen($rangeResponse->streamedContent()));

        // Test ETag presence
        $etag = $rangeResponse->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Test conditional 304 Not Modified
        $conditionalResponse = $this->get($signedUrl, [
            'If-None-Match' => $etag,
        ]);
        $conditionalResponse->assertStatus(304);
    }

    /**
     * 3. Test storage fallback route serves covers when webserver symlink is not available.
     */
    public function test_storage_fallback_route_serves_existing_public_cover(): void
    {
        $testImageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('covers/test_direct_fallback.png', $testImageContent);

        $response = $this->get('/storage/covers/test_direct_fallback.png');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    /**
     * 4. Test Book cover_url handles malformed prefixes cleanly.
     */
    public function test_book_cover_url_cleans_redundant_prefixes(): void
    {
        $book1 = new Book(['cover_image' => 'covers/artwork.jpg']);
        $this->assertStringEndsWith('/storage/covers/artwork.jpg', $book1->cover_url);

        $book2 = new Book(['cover_image' => 'storage/covers/artwork.jpg']);
        $this->assertStringEndsWith('/storage/covers/artwork.jpg', $book2->cover_url);

        $book3 = new Book(['cover_image' => 'public/covers/artwork.jpg']);
        $this->assertStringEndsWith('/storage/covers/artwork.jpg', $book3->cover_url);

        $book4 = new Book(['cover_image' => 'https://cdn.example.com/covers/artwork.jpg']);
        $this->assertEquals('https://cdn.example.com/covers/artwork.jpg', $book4->cover_url);
    }
}
