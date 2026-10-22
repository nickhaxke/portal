<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Executive Admin Dashboard with comprehensive real metrics.
     */
    public function index(): View
    {
        $booksCount = Book::count();
        $trashedBooksCount = Book::onlyTrashed()->count();
        $categoriesCount = Category::count();
        $pdfFilesCount = BookFile::where('file_type', 'pdf')->count();
        $epubFilesCount = BookFile::where('file_type', 'epub')->count();

        // Publications by type
        $rawTypes = Book::selectRaw('publication_type, count(*) as aggregate')
            ->groupBy('publication_type')
            ->pluck('aggregate', 'publication_type')
            ->toArray();

        $configuredTypes = config('portal.publication_types', [
            'book' => ['label' => 'Curated Books'],
            'research_paper' => ['label' => 'Research Papers'],
            'report' => ['label' => 'Executive Reports'],
            'policy_document' => ['label' => 'Policy Documents'],
            'thesis' => ['label' => 'Doctoral Theses'],
        ]);

        $booksByType = [];
        foreach ($configuredTypes as $key => $meta) {
            $booksByType[$key] = [
                'label' => is_array($meta) ? ($meta['name'] ?? $meta['label'] ?? ucwords(str_replace('_', ' ', $key))) : ucwords(str_replace('_', ' ', $key)),
                'badge' => is_array($meta) ? ($meta['badge'] ?? ucwords(str_replace('_', ' ', $key))) : ucwords(str_replace('_', ' ', $key)),
                'icon' => is_array($meta) ? ($meta['icon'] ?? 'book') : 'book',
                'count' => $rawTypes[$key] ?? 0,
            ];
        }

        // Publications by lifecycle status
        $rawStatuses = Book::selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();

        $statusCounts = [
            'published' => $rawStatuses['published'] ?? 0,
            'draft' => $rawStatuses['draft'] ?? 0,
            'archived' => $rawStatuses['archived'] ?? 0,
        ];

        // Reader accounts & verification
        $totalReaders = User::where('role', 'reader')->count();
        $verifiedReaders = User::where('role', 'reader')->whereNotNull('email_verified_at')->count();
        $adminStaffCount = User::whereIn('role', ['admin', 'superadmin', 'executive', 'editor'])->count();

        // Storage calculation (database record sum + disk check)
        $totalStorageBytes = (int) BookFile::sum('file_size');
        $totalStorageFormatted = $this->formatBytes($totalStorageBytes);

        // Recent publications
        $recentBooks = Book::with(['category', 'author', 'primaryPdf'])
            ->latest()
            ->take(6)
            ->get();

        // Categories / Disciplines distribution
        $categories = Category::withCount('books')
            ->orderByDesc('books_count')
            ->take(6)
            ->get();

        // Recent Audit Activity
        $recentAuditLogs = AuditLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        // Recent Import Batches
        $recentImports = ImportBatch::latest()
            ->take(5)
            ->get();

        // Real Reading Progress & Engagement
        $recentReadingActivity = ReadingProgress::with(['user', 'book'])
            ->whereNotNull('last_read_at')
            ->latest('last_read_at')
            ->take(6)
            ->get();
        $totalReadingSessions = ReadingProgress::count();
        $activeReadersCount = ReadingProgress::distinct('user_id')->count('user_id');

        return view('admin.dashboard', compact(
            'booksCount',
            'trashedBooksCount',
            'categoriesCount',
            'pdfFilesCount',
            'epubFilesCount',
            'booksByType',
            'statusCounts',
            'totalReaders',
            'verifiedReaders',
            'adminStaffCount',
            'totalStorageBytes',
            'totalStorageFormatted',
            'recentBooks',
            'categories',
            'recentAuditLogs',
            'recentImports',
            'recentReadingActivity',
            'totalReadingSessions',
            'activeReadersCount'
        ));
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
