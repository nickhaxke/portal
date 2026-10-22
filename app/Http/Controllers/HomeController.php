<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Insight;
use App\Models\Library;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the Executive Portal Landing Page.
     */
    public function index(): View
    {
        // 1. Curated Executive Hero Slides
        $heroSlides = HeroSlide::active()->ordered()->get();

        // 2. Knowledge Disciplines
        $categories = Category::withCount(['books' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        // 3. Featured Publications / Editor's Selection
        $featuredBooks = Book::published()
            ->with(['category', 'author'])
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(6)
            ->get();

        // If fewer than 6 featured, fill with latest published books
        if ($featuredBooks->count() < 6) {
            $extra = Book::published()
                ->with(['category', 'author'])
                ->whereNotIn('id', $featuredBooks->pluck('id'))
                ->latest('published_at')
                ->take(6 - $featuredBooks->count())
                ->get();
            $featuredBooks = $featuredBooks->merge($extra);
        }

        // 4. Curated Executive Insights with Editorial Imagery
        $insights = Insight::published()->ordered()->take(3)->get();

        // 5. Digital Libraries
        $libraries = Library::published()
            ->public()
            ->withCount(['books' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(6)
            ->get();

        // 6. Macro System Statistics
        $stats = [
            'books_count' => Book::published()->count(),
            'categories_count' => $categories->count(),
            'libraries_count' => Library::published()->public()->count(),
            'insights_count' => Insight::count(),
        ];

        return view('home', compact('heroSlides', 'categories', 'featuredBooks', 'insights', 'libraries', 'stats'));
    }
}
