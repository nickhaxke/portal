<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultipleLibraryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reader;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_lib_test@executive.portal'],
            [
                'name' => 'Admin Tester',
                'password' => bcrypt('Password123!'),
                'role' => 'admin',
            ]
        );

        $this->reader = User::firstOrCreate(
            ['email' => 'reader_lib_test@executive.portal'],
            [
                'name' => 'Reader Tester',
                'password' => bcrypt('Password123!'),
                'role' => 'reader',
            ]
        );

        $this->category = Category::firstOrCreate(
            ['slug' => 'test-strategy'],
            ['name' => 'Strategic Thinking', 'icon' => 'strategy', 'sort_order' => 1]
        );

        Storage::fake('public');
        Storage::fake('local');
    }

    /**
     * 1. Test administrator can view, create and persist a new library.
     */
    public function test_admin_can_create_library_with_visibility_and_icon(): void
    {
        $cover = UploadedFile::fake()->image('library_banner.jpg', 1200, 400);

        $response = $this->actingAs($this->admin)->post(route('admin.libraries.store'), [
            'name' => 'Executive Strategic Leadership Library',
            'description' => 'Dedicated library for C-suite governance, high-stakes decision making, and strategy.',
            'icon' => 'strategy',
            'visibility' => 'public',
            'is_published' => '1',
            'sort_order' => 2,
            'cover_image' => $cover,
        ]);

        $response->assertRedirect(route('admin.libraries.index'));
        $response->assertSessionHas('success');

        $library = Library::where('slug', 'executive-strategic-leadership-library')->first();
        $this->assertNotNull($library);
        $this->assertEquals('Executive Strategic Leadership Library', $library->name);
        $this->assertEquals('strategy', $library->icon);
        $this->assertTrue($library->is_published);
        $this->assertEquals('public', $library->visibility);
        $this->assertNotNull($library->cover_image);
        $this->assertTrue(Storage::disk('public')->exists($library->cover_image));
    }

    /**
     * 2. Test administrator can edit an existing library.
     */
    public function test_admin_can_edit_existing_library(): void
    {
        $library = Library::create([
            'name' => 'Original Library Name',
            'slug' => 'original-library-name',
            'description' => 'Original description',
            'icon' => 'library',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.libraries.update', $library->id), [
            'name' => 'Updated Library Name',
            'description' => 'Updated rich description',
            'icon' => 'shield',
            'visibility' => 'restricted',
            'is_published' => '1',
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.libraries.index'));
        $library->refresh();
        $this->assertEquals('Updated Library Name', $library->name);
        $this->assertEquals('shield', $library->icon);
        $this->assertEquals('restricted', $library->visibility);
        $this->assertEquals('Updated rich description', $library->description);
    }

    /**
     * 3. Test assigning books to library and library book counts.
     */
    public function test_assigning_books_to_library_updates_book_counts(): void
    {
        $library = Library::create([
            'name' => 'Policy & Governance Library',
            'slug' => 'policy-governance-library',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $author = Author::create(['name' => 'Dr. Marcus Vance', 'slug' => 'dr-marcus-vance']);

        $book1 = Book::create([
            'title' => 'Global Policy Systems',
            'slug' => 'global-policy-systems',
            'category_id' => $this->category->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $book2 = Book::create([
            'title' => 'Draft Policy Handbook',
            'slug' => 'draft-policy-handbook',
            'category_id' => $this->category->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'draft',
            'visibility' => 'restricted',
        ]);

        // Bulk assign both books to library
        $response = $this->actingAs($this->admin)->post(route('admin.libraries.assign-books', $library->id), [
            'book_ids' => [$book1->id, $book2->id],
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals($library->id, $book1->fresh()->library_id);
        $this->assertEquals($library->id, $book2->fresh()->library_id);

        $library->refresh();
        $this->assertEquals(2, $library->books()->count());
        $this->assertEquals(1, $library->publishedBooks()->count());
    }

    /**
     * 4. Test public library browsing and access policies.
     */
    public function test_public_and_restricted_library_browsing_visibility(): void
    {
        $publicLib = Library::create([
            'name' => 'Public Knowledge Library',
            'slug' => 'public-knowledge-library',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $privateLib = Library::create([
            'name' => 'Classified Board Archives',
            'slug' => 'classified-board-archives',
            'visibility' => 'private',
            'is_published' => true,
        ]);

        // Unauthenticated visitor visits /libraries
        $visitorResponse = $this->get(route('libraries.index'));
        $visitorResponse->assertStatus(200);
        $visitorResponse->assertSee('Public Knowledge Library');
        $visitorResponse->assertDontSee('Classified Board Archives');

        // Unauthenticated visitor accessing private library directly receives 404
        $privateResponse = $this->get(route('libraries.show', $privateLib->slug));
        $privateResponse->assertStatus(404);
    }

    /**
     * 5. Test search and filtering within a selected library.
     */
    public function test_search_and_filter_within_library(): void
    {
        $library = Library::create([
            'name' => 'Finance & Risk Library',
            'slug' => 'finance-risk-library',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $author = Author::create(['name' => 'Warren Graham', 'slug' => 'warren-graham']);

        $targetBook = Book::create([
            'library_id' => $library->id,
            'title' => 'Macroeconomic Risk Management',
            'slug' => 'macroeconomic-risk-management',
            'category_id' => $this->category->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $otherBook = Book::create([
            'library_id' => $library->id,
            'title' => 'Corporate Culture & Empathy',
            'slug' => 'corporate-culture-empathy',
            'category_id' => $this->category->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $response = $this->get(route('libraries.show', [
            'slug' => $library->slug,
            'q' => 'Macroeconomic',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Macroeconomic Risk Management');
        $response->assertDontSee('Corporate Culture & Empathy');
    }

    /**
     * 6. Test public home page displays the digital libraries discovery showcase.
     */
    public function test_home_page_displays_digital_libraries_discovery_showcase(): void
    {
        $lib = Library::create([
            'name' => 'Strategic Intelligence Library',
            'slug' => 'strategic-intelligence-library',
            'description' => 'Dedicated library for geopolitical and institutional intelligence.',
            'icon' => 'library',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Curated Digital Libraries');
        $response->assertSee('Strategic Intelligence Library');
        $response->assertSee(route('libraries.show', $lib->slug));
    }
}
