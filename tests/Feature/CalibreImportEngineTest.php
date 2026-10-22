<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ImportBatch;
use App\Models\Library;
use App\Models\User;
use App\Services\CalibreMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\GenerateCalibreFixture;
use Tests\TestCase;

class CalibreImportEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Library $library;

    protected string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'calibre_admin_test@executive.portal'],
            [
                'name' => 'Calibre Admin',
                'password' => bcrypt('Password123!'),
                'role' => 'admin',
            ]
        );

        $this->library = Library::create([
            'name' => 'Strategic Management Vault',
            'slug' => 'strategic-management-vault',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        Storage::fake('public');
        Storage::fake('local');

        // Create synthetic Calibre fixture
        $this->fixturePath = storage_path('framework/testing/calibre_test_fixture');
        GenerateCalibreFixture::create($this->fixturePath);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixturePath)) {
            File::deleteDirectory($this->fixturePath);
        }

        parent::tearDown();
    }

    /**
     * 1. Test Calibre import maps target library, tags, series, and authors.
     */
    public function test_calibre_import_maps_library_tags_and_series(): void
    {
        $service = app(CalibreMigrationService::class);

        $batch = $service->importLibrary(
            $this->fixturePath,
            50,
            null,
            false,
            null,
            $this->library->id,
            'skip'
        );

        $this->assertEquals('completed', $batch->status);
        $this->assertGreaterThanOrEqual(2, $batch->imported_count);

        $book1 = Book::where('calibre_id', 1)->first();
        $this->assertNotNull($book1);
        $this->assertEquals('The Effective Executive', $book1->title);
        $this->assertEquals('Peter F. Drucker', $book1->author_name);
        $this->assertEquals($this->library->id, $book1->library_id);
        $this->assertEquals('Executive Wisdom Series', $book1->series);
        $this->assertEquals(1.0, (float) $book1->series_number);
        $this->assertEquals('en', $book1->language);

        // Verify tags synced
        $tags = $book1->tags->pluck('name')->toArray();
        $this->assertContains('Management', $tags);
        $this->assertContains('Executive Strategy', $tags);
    }

    /**
     * 2. Test duplicate resolution mode 'skip' vs 'update'.
     */
    public function test_calibre_duplicate_mode_skip_and_update(): void
    {
        $service = app(CalibreMigrationService::class);

        // Run 1: initial import
        $batch1 = $service->importLibrary($this->fixturePath, 50, null, false, 1, $this->library->id, 'skip');
        $this->assertEquals(1, $batch1->imported_count);

        // Run 2: duplicate with skip mode
        $batch2 = $service->importLibrary($this->fixturePath, 50, null, false, 1, $this->library->id, 'skip');
        $this->assertEquals(0, $batch2->imported_count);
        $this->assertEquals(1, $batch2->duplicate_count);

        // Modify the imported book title in portal to test update
        $book = Book::where('calibre_id', 1)->first();
        $book->update(['title' => 'Old Modified Title']);

        // Run 3: duplicate with update mode
        $batch3 = $service->importLibrary($this->fixturePath, 50, null, false, 1, $this->library->id, 'update');
        $this->assertEquals(1, $batch3->imported_count);
        $book->refresh();
        $this->assertEquals('The Effective Executive', $book->title);
    }

    /**
     * 3. Test secondary CSV import engine.
     */
    public function test_secondary_csv_import_creates_books_tags_and_categories(): void
    {
        $csvContent = implode("\n", [
            'Title,Author,Publisher,Year,ISBN,Series,Series Index,Category,Tags,Description',
            '"High Output Management","Andrew S. Grove","Vintage Books",1995,"978-0679762881","Executive Series",1.0,"Leadership","Management, Strategy","The essential handbook for creating and maintaining a managerial mindset."',
            '"Thinking in Bets","Annie Duke","Portfolio",2018,"978-0735216358","","","Decision Science","Psychology, Risk","Making smarter decisions when you don\'t have all the facts."',
        ]);

        $csvFile = UploadedFile::fake()->createWithContent('catalogue_import.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.imports.csv'), [
            'csv_file' => $csvFile,
            'library_id' => $this->library->id,
            'duplicate_mode' => 'skip',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $book1 = Book::where('title', 'High Output Management')->first();
        $this->assertNotNull($book1);
        $this->assertEquals('Andrew S. Grove', $book1->author_name);
        $this->assertEquals($this->library->id, $book1->library_id);
        $this->assertEquals('Executive Series', $book1->series);
        $this->assertEquals('Leadership', $book1->category->name);

        $tags = $book1->tags->pluck('name')->toArray();
        $this->assertContains('Management', $tags);
        $this->assertContains('Strategy', $tags);

        $book2 = Book::where('title', 'Thinking in Bets')->first();
        $this->assertNotNull($book2);
        $this->assertEquals('Annie Duke', $book2->author_name);
        $this->assertEquals('Decision Science', $book2->category->name);
    }

    /**
     * 4. Test Web Scan and Execute routes with library selection.
     */
    public function test_web_scan_and_execute_with_library_assignment(): void
    {
        $scanResponse = $this->actingAs($this->admin)->post(route('admin.imports.scan'), [
            'library_path' => $this->fixturePath,
        ]);
        $scanResponse->assertStatus(200);
        $scanResponse->assertSee('The Effective Executive');

        $executeResponse = $this->actingAs($this->admin)->post(route('admin.imports.store'), [
            'library_path' => $this->fixturePath,
            'library_id' => $this->library->id,
            'duplicate_mode' => 'skip',
            'limit' => 2,
        ]);

        $executeResponse->assertRedirect();
        $batch = ImportBatch::latest()->first();
        $this->assertNotNull($batch);
        $this->assertEquals('completed', $batch->status);
    }
}
