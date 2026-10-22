<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $booksCount = Book::count();
        $readersCount = User::where('role', 'reader')->count();
        $totalStorageBytes = (int) BookFile::sum('file_size');
        $readingSessions = ReadingProgress::count();

        // Top 10 Most-Read Books
        $mostReadBooks = Book::withCount('readingProgress')
            ->orderByDesc('reading_progress_count')
            ->take(10)
            ->get();

        // Publications by type
        $publicationsByType = Book::selectRaw('publication_type, count(*) as count')
            ->groupBy('publication_type')
            ->pluck('count', 'publication_type')
            ->toArray();

        // Discipline distribution
        $disciplineStats = Category::withCount('books')
            ->orderByDesc('books_count')
            ->get();

        return view('admin.reports.index', compact(
            'booksCount',
            'readersCount',
            'totalStorageBytes',
            'readingSessions',
            'mostReadBooks',
            'publicationsByType',
            'disciplineStats'
        ));
    }

    /**
     * Export publications inventory as CSV.
     */
    public function exportBooksCsv(): StreamedResponse
    {
        $filename = 'executive-portal-inventory-'.date('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Publication Type', 'Author', 'Category', 'Status', 'Published Year', 'Pages', 'DOI', 'ISBN', 'Created At']);

            Book::with('category')->chunk(100, function ($books) use ($handle) {
                foreach ($books as $book) {
                    fputcsv($handle, [
                        $book->id,
                        $book->title,
                        $book->publication_type,
                        $book->author_name,
                        $book->category?->name ?? 'Unassigned',
                        $book->status,
                        $book->publication_year,
                        $book->pages_count,
                        $book->doi,
                        $book->isbn,
                        $book->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
