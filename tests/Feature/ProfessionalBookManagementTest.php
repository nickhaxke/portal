<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfessionalBookManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Library $library;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->library = Library::factory()->create([
            'name' => 'Strategic Intelligence Repository',
            'slug' => 'strategic-intelligence-repository',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Executive Governance',
            'slug' => 'executive-governance',
            'sort_order' => 1,
        ]);
    }

    private function createTestBook(array $attributes = []): Book
    {
        $title = $attributes['title'] ?? 'Sample Title '.uniqid();
        $authorName = $attributes['author_name'] ?? 'Dr. Sample Author';
        $author = Author::firstOrCreate(
            ['slug' => Str::slug($authorName)],
            ['name' => $authorName]
        );

        return Book::create(array_merge([
            'title' => $title,
            'slug' => Str::slug($title).'-'.rand(100, 999),
            'author_id' => $author->id,
            'author_name' => $author->name,
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
            'status' => 'published',
            'visibility' => 'public',
            'language' => 'English',
            'published_at' => now(),
        ], $attributes));
    }

    public function test_admin_can_create_book_with_full_metadata_tags_and_library(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'The Architecture of High-Stakes Decision Making',
            'subtitle' => 'Principles for Global Enterprise Leaders',
            'publication_type' => 'book',
            'library_id' => $this->library->id,
            'author_name' => 'Dr. Victoria Sterling',
            'publisher' => 'Cambridge Strategic Institute',
            'language' => 'English',
            'edition' => '1st Edition',
            'series' => 'Governance Monographs',
            'series_number' => 4,
            'category_id' => $this->category->id,
            'tags' => 'Governance, Strategic Foresight, Enterprise Risk',
            'description' => 'Comprehensive executive analysis on strategic risk governance.',
            'pages_count' => 320,
            'published_year' => '2026',
            'isbn' => '0-123456-78-9',
            'isbn13' => '978-0-123456-78-9',
            'status' => 'published',
            'allow_download' => true,
        ]);

        $response->assertRedirect(route('admin.books.index'));

        $this->assertDatabaseHas('books', [
            'title' => 'The Architecture of High-Stakes Decision Making',
            'subtitle' => 'Principles for Global Enterprise Leaders',
            'library_id' => $this->library->id,
            'series' => 'Governance Monographs',
            'series_number' => 4,
            'language' => 'English',
            'isbn13' => '978-0-123456-78-9',
            'pages_count' => 320,
        ]);

        $book = Book::where('title', 'The Architecture of High-Stakes Decision Making')->first();
        $this->assertNotNull($book);
        $this->assertEquals(3, $book->tags()->count());
        $this->assertTrue($book->tags()->where('name', 'Governance')->exists());
        $this->assertTrue($book->tags()->where('name', 'Strategic Foresight')->exists());
        $this->assertTrue($book->tags()->where('name', 'Enterprise Risk')->exists());
    }

    public function test_duplicate_detection_warns_on_matching_title_and_author(): void
    {
        // First book creation
        $this->createTestBook([
            'title' => 'Corporate Strategy Under Disruption',
            'author_name' => 'Prof. Marcus Vance',
            'isbn' => '978-1-111111-11-1',
        ]);

        // Attempting to create duplicate with matching title and author
        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Corporate Strategy Under Disruption',
            'author_name' => 'Prof. Marcus Vance',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
        ]);

        // Should return back with duplicate warning session
        $response->assertSessionHas('duplicate_warning');

        // Confirm duplicate override works
        $overrideResponse = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Corporate Strategy Under Disruption',
            'author_name' => 'Prof. Marcus Vance',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
            'confirm_duplicate' => 1,
        ]);

        $overrideResponse->assertRedirect(route('admin.books.index'));
        $this->assertEquals(2, Book::where('title', 'Corporate Strategy Under Disruption')->count());
    }

    public function test_duplicate_detection_warns_on_matching_isbn(): void
    {
        $this->createTestBook([
            'title' => 'Global Economics Overview',
            'author_name' => 'Author A',
            'isbn' => '978-3-16-148410-0',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Different Title But Same ISBN',
            'author_name' => 'Author B',
            'isbn' => '978-3-16-148410-0',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
        ]);

        $response->assertSessionHas('duplicate_warning');
    }

    public function test_admin_can_update_book_metadata_and_sync_tags(): void
    {
        $book = $this->createTestBook([
            'title' => 'Old Title',
            'author_name' => 'Old Author',
            'language' => 'English',
        ]);
        $book->syncTags('Leadership, Strategy');

        $this->assertEquals(2, $book->tags()->count());

        $response = $this->actingAs($this->admin)->put(route('admin.books.update', $book->id), [
            'title' => 'Updated Strategic Leadership Manual',
            'author_name' => 'Elena Rostova',
            'category_id' => $this->category->id,
            'library_id' => $this->library->id,
            'series' => 'Executive Master Series',
            'series_number' => 2,
            'language' => 'German',
            'isbn13' => '978-9-999999-99-9',
            'tags' => 'Strategy, Global Economy, Geopolitics',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.books.index'));

        $book->refresh();
        $this->assertEquals('Updated Strategic Leadership Manual', $book->title);
        $this->assertEquals('Elena Rostova', $book->author_name);
        $this->assertEquals('Executive Master Series', $book->series);
        $this->assertEquals(2, $book->series_number);
        $this->assertEquals('German', $book->language);
        $this->assertEquals('978-9-999999-99-9', $book->isbn13);

        $tagNames = $book->tags->pluck('name')->toArray();
        $this->assertContains('Strategy', $tagNames);
        $this->assertContains('Global Economy', $tagNames);
        $this->assertContains('Geopolitics', $tagNames);
        $this->assertNotContains('Leadership', $tagNames);
    }

    public function test_public_book_detail_displays_library_series_and_tags(): void
    {
        $book = $this->createTestBook([
            'title' => 'Executive Foresight in Uncertain Times',
            'author_name' => 'Dr. Julian Thorne',
            'series' => 'Future Studies',
            'series_number' => 1,
            'language' => 'English',
            'isbn13' => '978-0-987654-32-1',
            'status' => 'published',
        ]);
        $book->syncTags('Foresight, Scenario Planning');

        $response = $this->get(route('books.show', $book->slug));

        $response->assertOk();
        $response->assertSee('Strategic Intelligence Repository');
        $response->assertSee('Future Studies');
        $response->assertSee('English');
        $response->assertSee('978-0-987654-32-1');
        $response->assertSee('Foresight');
        $response->assertSee('Scenario Planning');
    }
}
