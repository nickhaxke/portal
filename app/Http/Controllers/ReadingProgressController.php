<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookBookmark;
use App\Models\ReadingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingProgressController extends Controller
{
    /**
     * Record or update reading progress beacon from the PDF reader.
     */
    public function updateProgress(Request $request, string $slug): JsonResponse
    {
        $book = Book::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'current_page' => 'required|integer|min:1',
            'total_pages' => 'required|integer|min:1',
        ]);

        $userId = auth()->id();
        if (! $userId) {
            // Guests can track in session if desired
            session()->put("reading_progress_{$book->id}", [
                'current_page' => $validated['current_page'],
                'total_pages' => $validated['total_pages'],
            ]);

            return response()->json([
                'success' => true,
                'is_guest' => true,
                'current_page' => $validated['current_page'],
            ]);
        }

        $currentPage = $validated['current_page'];
        $totalPages = max($validated['total_pages'], 1);
        $percent = round(($currentPage / $totalPages) * 100, 2);
        $isCompleted = ($currentPage >= $totalPages);

        $progress = ReadingProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'book_id' => $book->id,
            ],
            [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'percent_complete' => min(100.00, $percent),
                'is_completed' => $isCompleted,
                'last_read_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'is_guest' => false,
            'current_page' => $progress->current_page,
            'percent_complete' => $progress->percent_complete,
            'is_completed' => $progress->is_completed,
        ]);
    }

    /**
     * Add a bookmark on the current page.
     */
    public function addBookmark(Request $request, string $slug): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $book = Book::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'page_number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:1000',
        ]);

        $bookmark = BookBookmark::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'page_number' => $validated['page_number'],
            ],
            [
                'title' => $validated['title'] ?? ('Page '.$validated['page_number']),
                'note' => $validated['note'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'bookmark' => $bookmark,
        ]);
    }

    /**
     * Delete an existing bookmark.
     */
    public function removeBookmark(Request $request, string $slug, int $bookmarkId): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $bookmark = BookBookmark::where('id', $bookmarkId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $bookmark->delete();

        return response()->json(['success' => true]);
    }
}
