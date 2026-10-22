<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalPriorityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

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

        // Create Categories
        $this->category = Category::create([
            'name' => 'Leadership & Management',
            'slug' => 'leadership-management',
            'icon' => '♟',
            'description' => 'Executive leadership frameworks and decision architecture.',
            'sort_order' => 1,
        ]);
    }

    /**
     * Test Public Landing Page renders with dynamic data from database.
     */
    public function test_public_landing_page_renders_with_database_books_and_categories(): void
    {
        $book = Book::create([
            'title' => 'Dare to Lead',
            'slug' => 'dare-to-lead',
            'author_name' => 'Brené Brown',
            'category_id' => $this->category->id,
            'description' => 'A guide to courage and transformative leadership.',
            'cover_bg' => '#17565d',
            'cover_fg' => '#ffffff',
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Executive');
        $response->assertSee('Dare to Lead');
        $response->assertSee('Brené Brown');
        $response->assertSee('Leadership &amp; Management', false);
    }

    /**
     * Test Catalogue explore page with real-time query search and category filtering.
     */
    public function test_catalogue_explore_and_search(): void
    {
        $book1 = Book::create([
            'title' => 'The Lean Startup',
            'slug' => 'the-lean-startup',
            'author_name' => 'Eric Ries',
            'category_id' => $this->category->id,
            'description' => 'How to build agile products.',
        ]);

        $book2 = Book::create([
            'title' => 'Principles for Navigating Big Debt Crises',
            'slug' => 'principles-debt-crises',
            'author_name' => 'Ray Dalio',
            'category_id' => $this->category->id,
            'description' => 'Financial and macroeconomic principles.',
        ]);

        // 1. Explore page loads both
        $response = $this->get('/explore');
        $response->assertStatus(200);
        $response->assertSee('The Lean Startup');
        $response->assertSee('Principles for Navigating Big Debt Crises');

        // 2. Search for "Lean"
        $searchResponse = $this->get('/explore?q=Lean');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('The Lean Startup');
        $searchResponse->assertDontSee('Principles for Navigating Big Debt Crises');
    }

    /**
     * Test Book Details and Save to Personal Library.
     */
    public function test_book_details_and_save_to_library(): void
    {
        $book = Book::create([
            'title' => 'The 5 AM Club',
            'slug' => 'the-5-am-club',
            'author_name' => 'Robin Sharma',
            'category_id' => $this->category->id,
            'description' => 'A practical approach to routines, morning productivity and personal growth.',
        ]);

        // View details
        $response = $this->get('/books/'.$book->slug);
        $response->assertStatus(200);
        $response->assertSee('The 5 AM Club');
        $response->assertSee('Robin Sharma');
        $response->assertSee('Read PDF Online');

        // Toggle save to My Library as authenticated user
        $saveResponse = $this->actingAs($this->admin)->postJson('/books/'.$book->id.'/toggle-save');
        $saveResponse->assertStatus(200);
        $saveResponse->assertJson(['saved' => true]);

        // View My Library page
        $libraryResponse = $this->actingAs($this->admin)->get('/my-library');
        $libraryResponse->assertStatus(200);
        $libraryResponse->assertSee('The 5 AM Club');
    }

    /**
     * Test the full Priority Functional Workflow:
     * Admin Login → Add Book → Upload PDF/Cover → Save to Database → Display in Catalogue → Search → Read PDF Online
     */
    public function test_priority_functional_workflow_complete_slice(): void
    {
        // 1. Admin Login
        $loginResponse = $this->post('/login', [
            'email' => 'admin@executive.portal',
            'password' => 'Password123!',
        ]);
        $loginResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);

        // 2. Access Add Book Form
        $createFormResponse = $this->actingAs($this->admin)->get(route('admin.books.create'));
        $createFormResponse->assertStatus(200);
        $createFormResponse->assertSee('Add Executive Publication');

        // 3. Create Sample PDF file for upload
        $fakePdf = UploadedFile::fake()->create('governance-manifesto.pdf', 150, 'application/pdf');

        // 4. Submit Add Book Form with PDF upload and bespoke colors
        $storeResponse = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Executive Governance in the Age of AI',
            'author_name' => 'Prof. Amara Osei',
            'category_id' => $this->category->id,
            'description' => 'A definitive treatise on algorithmic oversight, board responsibilities and digital ethics.',
            'cover_bg' => '#102946',
            'cover_fg' => '#ffffff',
            'pages_count' => 312,
            'published_year' => '2026',
            'is_featured' => 1,
            'pdf_file' => $fakePdf,
        ]);

        $storeResponse->assertRedirect(route('admin.books.index'));

        // 5. Verify Database Records
        $savedBook = Book::where('title', 'Executive Governance in the Age of AI')->first();
        $this->assertNotNull($savedBook, 'Book should exist in database.');
        $this->assertEquals('Prof. Amara Osei', $savedBook->author_name);
        $this->assertEquals($this->category->id, $savedBook->category_id);
        $this->assertTrue($savedBook->is_featured);

        // Verify Author Record
        $this->assertDatabaseHas('authors', ['name' => 'Prof. Amara Osei']);

        // Verify BookFile Record
        $this->assertDatabaseHas('book_files', [
            'book_id' => $savedBook->id,
            'file_name' => 'governance-manifesto.pdf',
            'file_type' => 'pdf',
        ]);

        // 6. Verify Book Displays in Public Catalogue
        $exploreResponse = $this->get('/explore');
        $exploreResponse->assertStatus(200);
        $exploreResponse->assertSee('Executive Governance in the Age of AI');
        $exploreResponse->assertSee('Prof. Amara Osei');

        // 7. Verify Search finds the newly added book
        $searchResponse = $this->get('/explore?q=Governance');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Executive Governance in the Age of AI');

        // 8. Verify Book Detail Page
        $detailResponse = $this->get('/books/'.$savedBook->slug);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Executive Governance in the Age of AI');
        $detailResponse->assertSee('Prof. Amara Osei');

        // 9. Verify PDF Online Reader view loads
        $readerResponse = $this->get('/books/'.$savedBook->slug.'/reader');
        $readerResponse->assertStatus(200);
        $readerResponse->assertSee('Executive Governance in the Age of AI');
        $readerResponse->assertSee('pdf-canvas');

        // 10. Verify PDF Binary Stream returns application/pdf
        $streamResponse = $this->get('/books/'.$savedBook->slug.'/stream-pdf');
        $streamResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $streamResponse->headers->get('content-type'));
    }
}
