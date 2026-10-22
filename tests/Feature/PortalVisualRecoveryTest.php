<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\HeroSlide;
use App\Models\Insight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalVisualRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_keeps_search_and_editorial_headline_with_ordered_active_slides(): void
    {
        HeroSlide::create(['title' => 'Second highlight', 'image_path' => 'images/second.jpg', 'sort_order' => 2, 'is_active' => true]);
        HeroSlide::create(['title' => 'First highlight', 'image_path' => 'images/first.jpg', 'sort_order' => 1, 'is_active' => true, 'cta_text' => 'Explore leadership', 'cta_url' => '/explore?q=Leadership']);
        HeroSlide::create(['title' => 'Inactive highlight', 'image_path' => 'images/inactive.jpg', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->get('/');

        $response->assertOk()->assertSee('Better Tomorrow')->assertSee('Search books, authors and topics')
            ->assertSeeInOrder(['First highlight', 'Second highlight'])->assertDontSee('Inactive highlight')
            ->assertSee('Show slide 2')->assertSee('Explore leadership')->assertSee('/explore?q=Leadership', false);
    }

    public function test_home_has_bookshelf_search_and_honest_empty_shelf_without_active_slides(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Books on leadership, innovation, strategy, wisdom and growth')
            ->assertSee('Search books, authors and topics')->assertSee('The collection is being curated')
            ->assertDontSee('No books match your search criteria.');
    }

    public function test_unpublished_and_scheduled_insights_are_hidden_from_home_and_listing(): void
    {
        $this->freezeTime();
        Insight::create(['title' => 'Published analysis', 'content' => 'Public executive analysis.', 'published_at' => now()->subDay()]);
        Insight::create(['title' => 'Private draft analysis', 'content' => 'Internal draft.', 'published_at' => null]);
        Insight::create(['title' => 'Scheduled analysis', 'content' => 'Future analysis.', 'published_at' => now()->addDay()]);

        foreach (['/', '/insights'] as $url) {
            $this->get($url)->assertOk()->assertSee('Published analysis')
                ->assertDontSee('Private draft analysis')->assertDontSee('Scheduled analysis');
        }
        $this->get('/insights/private-draft-analysis')->assertNotFound();
        $this->get('/insights/scheduled-analysis')->assertNotFound();
    }

    public function test_uploaded_insight_image_excerpt_and_full_article_are_preserved(): void
    {
        Insight::create(['title' => 'Leadership evidence', 'content' => 'Full original article body.', 'excerpt' => 'An editorial introduction.', 'featured_image' => 'insights/leadership.jpg', 'image_alt' => 'Leaders in discussion', 'published_at' => now()->subDay()]);

        $this->get('/insights')->assertOk()->assertSee('An editorial introduction.')
            ->assertSee('Leaders in discussion')->assertSee('storage/insights/leadership.jpg', false);
        $this->get('/insights/leadership-evidence')->assertOk()->assertSee('Full original article body.')
            ->assertSee('Leaders in discussion');
    }

    public function test_invalid_legacy_slide_cta_uses_safe_catalogue_destination(): void
    {
        HeroSlide::create(['title' => 'Legacy highlight', 'image_path' => 'images/first.jpg', 'cta_text' => 'Browse the collection', 'cta_url' => 'javascript:alert(1)', 'is_active' => true]);

        $this->get('/')->assertOk()->assertSee('Browse the collection')->assertDontSee('javascript:alert(1)');
    }

    public function test_reader_navigation_does_not_offer_an_admin_destination(): void
    {
        $reader = User::factory()->create(['role' => 'reader']);

        $this->actingAs($reader)->get('/')->assertOk()->assertDontSee('Admin Portal')
            ->assertDontSee('href="'.route('admin.dashboard').'"', false)->assertSee('Logout');
    }

    public function test_cover_urls_follow_the_request_host_and_subdirectory_instead_of_the_preview_storage_host(): void
    {
        config(['filesystems.disks.public.url' => 'http://127.0.0.1:8001/storage']);
        $book = Book::create([
            'title' => 'Cover URL regression',
            'slug' => 'cover-url-regression',
            'author_name' => 'Test Author',
            'cover_image' => 'covers/leadership.jpg',
        ]);
        $this->withServerVariables([
            'SCRIPT_NAME' => '/portal/public/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
            'PHP_SELF' => '/portal/public/index.php',
        ]);

        $response = $this->get('http://localhost/portal/public/explore');

        $response->assertOk()->assertSee('http://localhost/portal/public/storage/covers/leadership.jpg', false)
            ->assertDontSee('http://127.0.0.1:8001/storage/covers/leadership.jpg', false);
    }
}
