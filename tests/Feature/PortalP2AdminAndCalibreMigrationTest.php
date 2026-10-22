<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\ImportBatch;
use App\Models\PortalSetting;
use App\Models\User;
use App\Services\CalibreMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\GenerateCalibreFixture;
use Tests\TestCase;

class PortalP2AdminAndCalibreMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reader;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        // Create Administrator
        $this->admin = User::factory()->create([
            'name' => 'Executive Administrator',
            'email' => 'admin@executive.portal',
            'role' => 'superadmin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Public Reader
        $this->reader = User::factory()->create([
            'name' => 'Jane Executive Reader',
            'email' => 'reader@company.com',
            'role' => 'reader',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Generate synthetic Calibre library fixture
        GenerateCalibreFixture::create(base_path('tests/Fixtures/calibre_test'));
    }

    public function test_admin_shell_dashboard_displays_real_metrics_and_navigation(): void
    {
        $category = Category::create(['name' => 'Geopolitics', 'slug' => 'geopolitics', 'icon' => '🌐']);
        Book::create([
            'title' => 'World Order Briefing',
            'slug' => 'world-order-briefing',
            'category_id' => $category->id,
            'author_name' => 'Henry Kissinger',
            'publication_type' => 'book',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Executive Intelligence Dashboard');
        $response->assertSee('Disciplines & Taxonomy', false);
        $response->assertSee('Executive Readers');
        $response->assertSee('Private Vault Storage');

        // Verify the 13 navigation modules are rendered
        $response->assertSee('1. Overview');
        $response->assertSee('2. Publications');
        $response->assertSee('3. Bulk Import & Calibre', false);
        $response->assertSee('4. Authors & Publishers', false);
        $response->assertSee('5. Disciplines & Categories', false);
        $response->assertSee('6. Executive Insights');
        $response->assertSee('7. Curated Collections');
        $response->assertSee('8. Readers & Members', false);
        $response->assertSee('9. Administrators & Roles', false);
        $response->assertSee('10. Reports & Analytics', false);
        $response->assertSee('11. Activity & Audit Logs', false);
        $response->assertSee('12. Portal Settings');
        $response->assertSee('13. System & Backups', false);
    }

    public function test_publication_lifecycle_draft_published_archived_and_scoping(): void
    {
        $category = Category::create(['name' => 'Strategy', 'slug' => 'strategy']);

        // 1. Create Draft Publication
        $response = $this->actingAs($this->admin)->post('/admin/books', [
            'title' => 'Unpublished Internal Analysis',
            'author_name' => 'Michael Porter',
            'category_id' => $category->id,
            'publication_type' => 'report',
            'status' => 'draft',
        ]);
        $response->assertRedirect('/admin/books');

        $book = Book::where('title', 'Unpublished Internal Analysis')->firstOrFail();
        $this->assertEquals('draft', $book->status);

        // 2. Draft should NOT appear on public explore catalogue
        $this->flushSession();
        $exploreResponse = $this->get('/explore');
        $exploreResponse->assertDontSee('Unpublished Internal Analysis');

        // 3. Publish Book
        $this->actingAs($this->admin)->put("/admin/books/{$book->id}", [
            'title' => 'Unpublished Internal Analysis',
            'author_name' => 'Michael Porter',
            'category_id' => $category->id,
            'publication_type' => 'report',
            'status' => 'published',
        ]);

        $this->assertEquals('published', $book->fresh()->status);
        $this->assertNotNull($book->fresh()->published_at);

        // 4. Published book MUST appear on public explore page
        $this->flushSession();
        $exploreResponse2 = $this->get('/explore');
        $exploreResponse2->assertSee('Unpublished Internal Analysis');

        // 5. Archive Book
        $this->actingAs($this->admin)->put("/admin/books/{$book->id}", [
            'title' => 'Unpublished Internal Analysis',
            'author_name' => 'Michael Porter',
            'category_id' => $category->id,
            'publication_type' => 'report',
            'status' => 'archived',
        ]);

        $this->assertEquals('archived', $book->fresh()->status);
        $this->flushSession();
        $exploreResponse3 = $this->get('/explore');
        $exploreResponse3->assertDontSee('Unpublished Internal Analysis');
    }

    public function test_publication_soft_delete_restore_and_force_delete(): void
    {
        $category = Category::create(['name' => 'Economics', 'slug' => 'economics']);
        $book = Book::create([
            'title' => 'Monetary Policy Framework',
            'slug' => 'monetary-policy-framework',
            'category_id' => $category->id,
            'author_name' => 'Milton Friedman',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // 1. Soft delete
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/books/{$book->id}");
        $deleteResponse->assertRedirect('/admin/books');

        $this->assertTrue($book->fresh()->trashed());
        $this->assertCount(1, Book::onlyTrashed()->get());

        // 2. Restore
        $restoreResponse = $this->actingAs($this->admin)->post("/admin/books/{$book->id}/restore");
        $restoreResponse->assertRedirect();
        $this->assertFalse($book->fresh()->trashed());

        // 3. Force Delete
        $forceResponse = $this->actingAs($this->admin)->delete("/admin/books/{$book->id}/force");
        $forceResponse->assertRedirect();
        $this->assertNull(Book::withTrashed()->find($book->id));
    }

    public function test_publication_bulk_actions(): void
    {
        $category = Category::create(['name' => 'Operations', 'slug' => 'operations']);
        $book1 = Book::create(['title' => 'Book One', 'slug' => 'b1', 'category_id' => $category->id, 'author_name' => 'A1', 'status' => 'draft']);
        $book2 = Book::create(['title' => 'Book Two', 'slug' => 'b2', 'category_id' => $category->id, 'author_name' => 'A2', 'status' => 'draft']);

        // Bulk Publish
        $this->actingAs($this->admin)->post('/admin/books/bulk', [
            'action' => 'publish',
            'book_ids' => [$book1->id, $book2->id],
        ]);

        $this->assertEquals('published', $book1->fresh()->status);
        $this->assertEquals('published', $book2->fresh()->status);

        // Bulk Soft Delete
        $this->actingAs($this->admin)->post('/admin/books/bulk', [
            'action' => 'soft_delete',
            'book_ids' => [$book1->id, $book2->id],
        ]);

        $this->assertTrue($book1->fresh()->trashed());
        $this->assertTrue($book2->fresh()->trashed());

        // Bulk Restore
        $this->actingAs($this->admin)->post('/admin/books/bulk', [
            'action' => 'restore',
            'book_ids' => [$book1->id, $book2->id],
        ]);

        $this->assertFalse($book1->fresh()->trashed());
        $this->assertFalse($book2->fresh()->trashed());
    }

    public function test_calibre_migration_engine_scan_and_import_with_idempotency(): void
    {
        $fixturePath = base_path('tests/Fixtures/calibre_test');
        $service = app(CalibreMigrationService::class);

        // 1. Validation & Scan
        $scan = $service->scanLibrary($fixturePath);
        $this->assertTrue($scan['valid']);
        $this->assertEquals(3, $scan['books_count']);
        $this->assertEquals(3, $scan['authors_count']);

        // 2. First Import
        $batch = $service->importLibrary($fixturePath, 50);
        $this->assertInstanceOf(ImportBatch::class, $batch);
        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(3, $batch->imported_count);
        $this->assertEquals(0, $batch->duplicate_count);

        // Verify Imported Book & Identifiers
        $drucker = Book::where('calibre_id', 1)->first();
        $this->assertNotNull($drucker);
        $this->assertEquals('The Effective Executive', $drucker->title);
        $this->assertEquals('978-0060833459', $drucker->isbn);
        $this->assertEquals('10.1000/drucker-exec', $drucker->doi);
        $this->assertNotEmpty($drucker->files);
        $this->assertNotNull($drucker->primaryPdf->file_hash);

        // Verify Multi-Author Relationship
        $principles = Book::where('calibre_id', 2)->first();
        $this->assertNotNull($principles);
        $this->assertCount(2, $principles->authors);

        // 3. Idempotent Re-Import (Must NOT duplicate records)
        $batch2 = $service->importLibrary($fixturePath, 50);
        $this->assertEquals(0, $batch2->imported_count);
        $this->assertEquals(3, $batch2->duplicate_count);

        // Total count in database must remain exactly 3
        $this->assertEquals(3, Book::whereNotNull('calibre_id')->count());
    }

    public function test_category_circular_hierarchy_prevention(): void
    {
        $parent = Category::create(['name' => 'Executive Leadership', 'slug' => 'exec-lead', 'icon' => '♟']);

        $response = $this->actingAs($this->admin)->put("/admin/categories/{$parent->id}", [
            'name' => 'Executive Leadership',
            'parent_id' => $parent->id, // Circular: setting parent to self
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_author_management_and_merge_workflow(): void
    {
        $category = Category::create(['name' => 'General', 'slug' => 'general']);

        $authorA = Author::create(['name' => 'Peter Drucker', 'slug' => 'peter-drucker']);
        $authorB = Author::create(['name' => 'P. F. Drucker', 'slug' => 'p-f-drucker']);

        $book1 = Book::create(['title' => 'Book A', 'slug' => 'bk-a', 'category_id' => $category->id, 'author_id' => $authorA->id, 'author_name' => $authorA->name]);
        $book2 = Book::create(['title' => 'Book B', 'slug' => 'bk-b', 'category_id' => $category->id, 'author_id' => $authorB->id, 'author_name' => $authorB->name]);

        // Merge author B into author A
        $response = $this->actingAs($this->admin)->post("/admin/authors/{$authorB->id}/merge", [
            'target_author_id' => $authorA->id,
        ]);
        $response->assertRedirect('/admin/authors');

        $this->assertNull(Author::find($authorB->id));
        $this->assertEquals($authorA->id, $book2->fresh()->author_id);
        $this->assertEquals('Peter Drucker', $book2->fresh()->author_name);
    }

    public function test_curated_collections_cms(): void
    {
        $category = Category::create(['name' => 'Leadership', 'slug' => 'leadership']);
        $book = Book::create(['title' => 'Great By Choice', 'slug' => 'great-by-choice', 'category_id' => $category->id, 'author_name' => 'Jim Collins']);

        $response = $this->actingAs($this->admin)->post('/admin/collections', [
            'name' => 'C-Suite Strategy Anthology',
            'description' => 'Essential reading for chief executives.',
            'is_featured' => 1,
            'is_published' => 1,
            'book_ids' => [$book->id],
        ]);
        $response->assertRedirect('/admin/collections');

        $collection = Collection::where('name', 'C-Suite Strategy Anthology')->first();
        $this->assertNotNull($collection);
        $this->assertTrue($collection->is_featured);
        $this->assertCount(1, $collection->books);
    }

    public function test_reader_administration_status_toggle_and_email_verify(): void
    {
        $unverifiedReader = User::factory()->create([
            'role' => 'reader',
            'status' => 'active',
            'email_verified_at' => null,
        ]);

        // Suspend
        $this->actingAs($this->admin)->post("/admin/readers/{$unverifiedReader->id}/toggle-status");
        $this->assertEquals('suspended', $unverifiedReader->fresh()->status);

        // Activate
        $this->actingAs($this->admin)->post("/admin/readers/{$unverifiedReader->id}/toggle-status");
        $this->assertEquals('active', $unverifiedReader->fresh()->status);

        // Manually Verify Email
        $this->actingAs($this->admin)->post("/admin/readers/{$unverifiedReader->id}/verify-email");
        $this->assertTrue($unverifiedReader->fresh()->hasVerifiedEmail());
    }

    public function test_audit_logging_on_administrative_mutations(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Institutional Governance',
            'icon' => '🏛',
        ]);

        $audit = AuditLog::where('action', 'category.create')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->admin->id, $audit->user_id);
        $this->assertStringContainsString('Institutional Governance', json_encode($audit->new_values));
    }

    public function test_portal_settings_and_export_csv(): void
    {
        // Update portal settings
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'portal_name' => 'Custom Executive Portal',
            'portal_tagline' => 'Leadership Monograph Suite',
            'contact_email' => 'director@executive.portal',
            'registration_policy' => 'approval_required',
            'max_upload_size_mb' => 100,
        ]);
        $response->assertRedirect('/admin/settings');

        $this->assertEquals('Custom Executive Portal', PortalSetting::where('key', 'portal_name')->value('value'));
        $this->assertEquals('approval_required', PortalSetting::where('key', 'registration_policy')->value('value'));

        // Test Inventory CSV Export
        $csvResponse = $this->actingAs($this->admin)->get('/admin/reports/export-books');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
