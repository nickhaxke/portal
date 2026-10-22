<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBookCataloguingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reader;

    protected Library $library;

    protected Category $category;

    protected Category $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Executive Chief Librarian',
            'email' => 'librarian@executive.portal',
            'password' => Hash::make('SecretLibrarian2026!'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->reader = User::create([
            'name' => 'Authenticated Fellow Reader',
            'email' => 'fellow@oxford.edu',
            'password' => Hash::make('ReaderPass2026!'),
            'role' => 'reader',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->library = Library::create([
            'name' => 'Executive Intelligence Repository',
            'slug' => 'executive-intelligence-repository',
            'description' => 'Flagship institutional research archive.',
            'is_published' => true,
            'visibility' => 'public',
            'sort_order' => 1,
        ]);

        $this->category = Category::create([
            'name' => 'Strategic Governance',
            'slug' => 'strategic-governance',
            'sort_order' => 1,
        ]);

        $this->subCategory = Category::create([
            'name' => 'Board Architecture',
            'slug' => 'board-architecture',
            'parent_id' => $this->category->id,
            'sort_order' => 2,
        ]);
    }

    /**
     * Phase 1 & 2: Test that the create form renders with all 6 organized cataloguing sections.
     */
    public function test_admin_can_view_create_form_with_all_6_sections(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.books.create'));

        $response->assertOk();

        // Section 1: Basic Information
        $response->assertSee('Basic Information');
        $response->assertSee('Publication Title');
        $response->assertSee('Subtitle / Monograph Qualifier');
        $response->assertSee('Publication Type');

        // Section 2: Authors & Contributors
        $response->assertSee('Authors');
        $response->assertSee('Primary / Lead Author');
        $response->assertSee('Contributing Co-Authors');
        $response->assertSee('Translators');

        // Section 3: Classification & Discovery
        $response->assertSee('Classification');
        $response->assertSee('Assigned Digital Library');
        $response->assertSee('Primary Knowledge Discipline');
        $response->assertSee('Secondary Disciplines');
        $response->assertSee('Keywords');

        // Section 4: Publishing Metadata
        $response->assertSee('Publishing Metadata');
        $response->assertSee('Publisher / Press');
        $response->assertSee('Publication Year');
        $response->assertSee('Edition / Revision');
        $response->assertSee('ISBN-10');
        $response->assertSee('ISBN-13');
        $response->assertSee('Digital Object Identifier (DOI)');

        // Section 5: Documents & Cover
        $response->assertSee('Documents');
        $response->assertSee('Cover Manifestation');
        $response->assertSee('Executive Document Upload');
        $response->assertSee('Total Catalogued Page Count');
        $response->assertSee('Document Download Permissions');
        $response->assertSee('Upload Cover Artwork');

        // Section 6: Publishing & Access Governance
        $response->assertSee('Lifecycle');
        $response->assertSee('Access Governance');
        $response->assertSee('Lifecycle Status');
        $response->assertSee('Access Clearance Level');

        // Dual Actions
        $response->assertSee('Publish to Catalogue &rarr;', false);
        $response->assertSee('Save as Draft');
    }

    /**
     * Phase 2 & 3: Test creating a publication as a Draft and verify it is hidden from public explore.
     */
    public function test_admin_can_create_book_as_draft_and_draft_is_hidden_from_public_catalogue(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Confidential Strategic Briefing 2026',
            'subtitle' => 'Internal Institutional Review',
            'author_name' => 'Dr. Victoria Vance',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
            'publication_type' => 'report',
            'published_year' => '2026',
            'save_action' => 'draft',
        ]);

        $response->assertRedirect(route('admin.books.index'));

        $book = Book::where('title', 'Confidential Strategic Briefing 2026')->firstOrFail();
        $this->assertEquals('draft', $book->status);

        // Guest visitor explores catalogue -> Draft must NOT be visible
        $this->flushSession();
        $guestExplore = $this->get(route('explore'));
        $guestExplore->assertDontSee('Confidential Strategic Briefing 2026');
    }

    /**
     * Phase 2, 3 & 4: Test publishing with multi-contributor roles (Lead, Co-Author, Editor) and access clearance.
     */
    public function test_admin_can_publish_book_with_multi_attribution_and_access_clearance(): void
    {
        $coAuthor = Author::create(['name' => 'Prof. Marcus Vance', 'slug' => 'prof-marcus-vance']);
        $editor = Author::create(['name' => 'Elena Rostova', 'slug' => 'elena-rostova']);

        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Institutional Decision Architecture',
            'subtitle' => 'Frameworks for Sovereign Boards',
            'publication_type' => 'book',
            'author_name' => 'Dr. Victoria Sterling',
            'co_author_ids' => [$coAuthor->id],
            'editor_ids' => [$editor->id],
            'library_id' => $this->library->id,
            'category_id' => $this->category->id,
            'secondary_category_ids' => [$this->subCategory->id],
            'publisher' => 'Cambridge Strategic Institute',
            'published_year' => '2026',
            'publication_year' => 2026,
            'edition' => '1st Edition',
            'series' => 'Governance Monographs',
            'series_number' => 5,
            'language' => 'English',
            'isbn' => '0-123456-78-9',
            'isbn13' => '978-0-123456-78-9',
            'doi' => '10.1093/gov/2026.01',
            'pages_count' => 380,
            'tags' => 'Governance, Board Risk, Strategy',
            'description' => 'A definitive treatise on sovereign decision architecture.',
            'status' => 'published',
            'visibility' => 'restricted',
            'allow_download' => true,
            'is_featured' => true,
            'save_action' => 'publish',
        ]);

        $response->assertRedirect(route('admin.books.index'));

        $book = Book::where('title', 'Institutional Decision Architecture')->firstOrFail();

        // Verify status and visibility
        $this->assertEquals('published', $book->status);
        $this->assertEquals('restricted', $book->visibility);
        $this->assertTrue($book->allow_download);
        $this->assertTrue($book->is_featured);
        $this->assertEquals(2026, $book->publication_year);
        $this->assertEquals('2026', $book->published_year);

        // Verify author pivot roles
        $authors = $book->authors()->get();
        $this->assertCount(3, $authors);

        $lead = $authors->firstWhere('name', 'Dr. Victoria Sterling');
        $this->assertNotNull($lead);
        $this->assertEquals('lead_author', $lead->pivot->role);
        $this->assertEquals(1, $lead->pivot->sort_order);

        $co = $authors->firstWhere('name', 'Prof. Marcus Vance');
        $this->assertNotNull($co);
        $this->assertEquals('co_author', $co->pivot->role);
        $this->assertEquals(2, $co->pivot->sort_order);

        $ed = $authors->firstWhere('name', 'Elena Rostova');
        $this->assertNotNull($ed);
        $this->assertEquals('editor', $ed->pivot->role);
        $this->assertEquals(3, $ed->pivot->sort_order);

        // Clear authentication so subsequent requests are unauthenticated guest requests
        auth()->logout();
        $this->app['auth']->forgetGuards();

        $detailResponse = $this->get(route('books.show', $book->slug));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Dr. Victoria Sterling');
        $detailResponse->assertSee('Lead Author');
        $detailResponse->assertSee('Prof. Marcus Vance');
        $detailResponse->assertSee('Co Author');
        $detailResponse->assertSee('Elena Rostova');
        $detailResponse->assertSee('Editor');
        $detailResponse->assertSee('Sign In to Read &rarr;', false);
        $detailResponse->assertSee('Institutional Reader Authentication Required');
    }

    /**
     * Phase 5: Test real end-to-end cataloguing journey:
     * Admin Login -> Create Book -> Assign Author -> Select Library -> Upload Cover & PDF -> Publish -> View Public Catalogue -> View Book Details -> Read Online.
     */
    public function test_end_to_end_cataloguing_journey_create_publish_catalogue_details_read_online(): void
    {
        // 1. Create fake PDF and Cover assets
        $fakeCover = UploadedFile::fake()->image('sovereign_cover.jpg', 600, 900);
        $fakePdf = UploadedFile::fake()->create('sovereign_document.pdf', 1024, 'application/pdf');

        // 2. Admin submits complete publication
        $createResponse = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Sovereign Institutional Leadership 2026',
            'subtitle' => 'Global Governance Systems',
            'publication_type' => 'book',
            'author_name' => 'Prof. Katherine Sterling',
            'library_id' => $this->library->id,
            'category_id' => $this->category->id,
            'published_year' => '2026',
            'cover_image' => $fakeCover,
            'pdf_file' => $fakePdf,
            'status' => 'published',
            'visibility' => 'public',
            'allow_download' => true,
            'save_action' => 'publish',
        ]);

        $createResponse->assertRedirect(route('admin.books.index'));

        $book = Book::where('title', 'Sovereign Institutional Leadership 2026')->firstOrFail();
        $this->assertNotNull($book->cover_image);
        Storage::disk('public')->assertExists($book->cover_image);

        $activePdf = $book->primaryPdf;
        $this->assertNotNull($activePdf);
        Storage::disk('local')->assertExists($activePdf->file_path);

        // 3. View Public Catalogue: publication is searchable and visible
        $catalogueResponse = $this->get(route('explore', ['q' => 'Sovereign Institutional Leadership']));
        $catalogueResponse->assertOk();
        $catalogueResponse->assertSee('Sovereign Institutional Leadership 2026');

        // 4. View Book Details: public page renders with single reader action
        $detailResponse = $this->get(route('books.show', $book->slug));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Sovereign Institutional Leadership 2026');
        $detailResponse->assertSee('Prof. Katherine Sterling');
        $detailResponse->assertSee('Read PDF Online &rarr;', false);

        // 5. Launch Reader: online reader stream returns 200 OK
        $readerResponse = $this->get(route('books.reader', $book->slug));
        $readerResponse->assertOk();
        $readerResponse->assertSee('Sovereign Institutional Leadership 2026');
    }

    /**
     * Phase 4: Test Edit workflow preserves existing files when only metadata is updated.
     */
    public function test_edit_workflow_preserves_existing_files_when_metadata_updated(): void
    {
        $fakeCover = UploadedFile::fake()->image('existing_cover.jpg', 600, 900);
        $fakePdf = UploadedFile::fake()->create('existing_doc.pdf', 500, 'application/pdf');

        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Original Title Monograph',
            'author_name' => 'Original Author',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
            'cover_image' => $fakeCover,
            'pdf_file' => $fakePdf,
            'published_year' => '2025',
            'status' => 'published',
        ]);

        $book = Book::where('title', 'Original Title Monograph')->firstOrFail();
        $originalCoverPath = $book->cover_image;
        $originalPdfFile = $book->primaryPdf;

        $this->assertNotNull($originalCoverPath);
        $this->assertNotNull($originalPdfFile);

        // Load Edit Form: renders active file info
        $editFormResponse = $this->actingAs($this->admin)->get(route('admin.books.edit', $book->id));
        $editFormResponse->assertOk();
        $editFormResponse->assertSee($originalPdfFile->file_name);
        $editFormResponse->assertSee('Current Attached Document');
        $editFormResponse->assertSee('Custom Cover Active');

        // Update metadata WITHOUT uploading replacement files
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.books.update', $book->id), [
            'title' => 'Updated Title Monograph',
            'author_name' => 'Original Author',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
            'publisher' => 'New Academic Press',
            'published_year' => '2026',
            'status' => 'published',
        ]);

        $updateResponse->assertRedirect(route('admin.books.index'));

        $book->refresh();
        $this->assertEquals('Updated Title Monograph', $book->title);
        $this->assertEquals('New Academic Press', $book->publisher);

        // Assets MUST remain completely intact
        $this->assertEquals($originalCoverPath, $book->cover_image);
        Storage::disk('public')->assertExists($originalCoverPath);

        $this->assertEquals($originalPdfFile->id, $book->primaryPdf->id);
        Storage::disk('local')->assertExists($originalPdfFile->file_path);
    }

    /**
     * Phase 4: Test replacing cover updates only the target book without cross-book leakage.
     */
    public function test_edit_workflow_replaces_cover_and_updates_only_selected_book(): void
    {
        $coverA = UploadedFile::fake()->image('book_a_cover.jpg', 600, 900);
        $coverB = UploadedFile::fake()->image('book_b_cover.jpg', 600, 900);

        // Book A
        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Book Alpha',
            'author_name' => 'Author A',
            'category_id' => $this->category->id,
            'cover_image' => $coverA,
            'published_year' => '2025',
        ]);
        $bookA = Book::where('title', 'Book Alpha')->firstOrFail();
        $bookACoverOriginal = $bookA->cover_image;

        // Book B
        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Book Beta',
            'author_name' => 'Author B',
            'category_id' => $this->category->id,
            'cover_image' => $coverB,
            'published_year' => '2025',
        ]);
        $bookB = Book::where('title', 'Book Beta')->firstOrFail();
        $bookBCoverOriginal = $bookB->cover_image;

        // Replace Book A cover
        $newCoverA = UploadedFile::fake()->image('book_a_replacement.jpg', 600, 900);
        $this->actingAs($this->admin)->put(route('admin.books.update', $bookA->id), [
            'title' => 'Book Alpha Updated',
            'author_name' => 'Author A',
            'category_id' => $this->category->id,
            'cover_image' => $newCoverA,
        ]);

        $bookA->refresh();
        $bookB->refresh();

        // Book A has new cover and old cover was removed
        $this->assertNotEquals($bookACoverOriginal, $bookA->cover_image);
        Storage::disk('public')->assertExists($bookA->cover_image);
        Storage::disk('public')->assertMissing($bookACoverOriginal);

        // Book B is 100% untouched
        $this->assertEquals($bookBCoverOriginal, $bookB->cover_image);
        Storage::disk('public')->assertExists($bookBCoverOriginal);
    }

    /**
     * Phase 4: Test that replacing the PDF document creates version 2 and deactivates version 1.
     */
    public function test_edit_workflow_replaces_pdf_document_with_new_version(): void
    {
        $pdfV1 = UploadedFile::fake()->create('doc_v1.pdf', 300, 'application/pdf');

        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Versioning Test Monograph',
            'author_name' => 'Dr. Versioning',
            'category_id' => $this->category->id,
            'pdf_file' => $pdfV1,
        ]);

        $book = Book::where('title', 'Versioning Test Monograph')->firstOrFail();
        $fileV1 = $book->primaryPdf;
        $this->assertEquals(1, $fileV1->version);
        $this->assertTrue((bool) $fileV1->is_active);

        // Upload replacement PDF
        $pdfV2 = UploadedFile::fake()->create('doc_v2_revised.pdf', 450, 'application/pdf');
        $this->actingAs($this->admin)->put(route('admin.books.update', $book->id), [
            'title' => 'Versioning Test Monograph',
            'author_name' => 'Dr. Versioning',
            'category_id' => $this->category->id,
            'pdf_file' => $pdfV2,
        ]);

        $book->refresh();
        $fileV1->refresh();

        // Version 1 is deactivated
        $this->assertFalse((bool) $fileV1->is_active);

        // Version 2 is active
        $fileV2 = $book->primaryPdf;
        $this->assertNotNull($fileV2);
        $this->assertEquals(2, $fileV2->version);
        $this->assertEquals('doc_v2_revised.pdf', $fileV2->file_name);
        $this->assertTrue((bool) $fileV2->is_active);
    }

    /**
     * Phase 4: Test removing custom cover reverts cleanly to bespoke monogram palette.
     */
    public function test_remove_custom_cover_reverts_to_bespoke_monogram_palette(): void
    {
        $cover = UploadedFile::fake()->image('custom_art.jpg', 600, 900);

        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Monogram Revert Test',
            'author_name' => 'Prof. Monogram',
            'category_id' => $this->category->id,
            'cover_image' => $cover,
            'cover_bg' => '#102946',
            'cover_fg' => '#ffffff',
        ]);

        $book = Book::where('title', 'Monogram Revert Test')->firstOrFail();
        $coverPath = $book->cover_image;
        Storage::disk('public')->assertExists($coverPath);

        // Remove cover artwork
        $this->actingAs($this->admin)->put(route('admin.books.update', $book->id), [
            'title' => 'Monogram Revert Test',
            'author_name' => 'Prof. Monogram',
            'category_id' => $this->category->id,
            'remove_cover' => 1,
            'cover_bg' => '#12669b',
            'cover_fg' => '#ffffff',
        ]);

        $book->refresh();
        $this->assertNull($book->cover_image);
        $this->assertEquals('#12669b', $book->cover_bg);
        Storage::disk('public')->assertMissing($coverPath);
    }

    /**
     * Phase 3: Test duplicate title & author warning and confirmation override.
     */
    public function test_duplicate_warning_requires_confirmation_to_proceed(): void
    {
        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Unique Treatise on Geopolitics',
            'author_name' => 'Henry Kissinger',
            'category_id' => $this->category->id,
        ]);

        // Attempting duplicate without confirm_duplicate
        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Unique Treatise on Geopolitics',
            'author_name' => 'Henry Kissinger',
            'category_id' => $this->category->id,
        ]);

        $response->assertSessionHas('duplicate_warning');

        // Confirming duplicate proceeds
        $confirmResponse = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Unique Treatise on Geopolitics',
            'author_name' => 'Henry Kissinger',
            'category_id' => $this->category->id,
            'confirm_duplicate' => 1,
        ]);

        $confirmResponse->assertRedirect(route('admin.books.index'));
        $this->assertEquals(2, Book::where('title', 'Unique Treatise on Geopolitics')->count());
    }

    /**
     * Phase 3: Test validation errors prevent execution and return session errors.
     */
    public function test_validation_errors_render_inline_and_prevent_creation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => '', // missing title
            'author_name' => '', // missing author
            'category_id' => 999999, // invalid category
        ]);

        $response->assertSessionHasErrors(['title', 'author_name', 'category_id']);
        $this->assertEquals(0, Book::count());
    }
}
