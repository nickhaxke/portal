<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookFile;
use App\Services\PublicationAccessService;
use App\Services\SimplePdfBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReaderController extends Controller
{
    public function __construct(
        protected PublicationAccessService $accessService
    ) {}

    /**
     * Display the Executive PDF Reader interface.
     */
    public function read(Request $request, string $slug)
    {
        $book = Book::with(['category', 'author', 'primaryPdf'])->where('slug', $slug)->firstOrFail();

        // If publication is not published and user cannot view it, abort 404
        if (! $this->accessService->canView(auth()->user(), $book)) {
            abort(404);
        }

        // Enforce document access authorization
        if (! $this->accessService->canReadOnline(auth()->user(), $book)) {
            if (! auth()->check()) {
                return redirect()->guest(route('login'))
                    ->with('error', 'Sign in with an authorized account to access this executive publication.');
            }
            abort(403, 'You do not have authorization to view this publication.');
        }

        // Authorize session for subsequent stream requests
        $request->session()->put("authorized_reader_{$book->id}", true);

        $pdfFile = $book->primaryPdf;
        $streamUrl = $this->accessService->generateSignedStreamUrl($book);
        $canDownload = $this->accessService->canDownload(auth()->user(), $book);

        // Retrieve reading position and bookmarks
        $initialPage = 1;
        $bookmarks = collect();

        if (auth()->check()) {
            $progress = $book->userProgress(auth()->id());
            if ($progress && $progress->current_page > 1) {
                $initialPage = $progress->current_page;
            }
            $bookmarks = $book->bookmarks()->where('user_id', auth()->id())->get();
        } else {
            $sessionProgress = session("reading_progress_{$book->id}");
            if (! empty($sessionProgress['current_page'])) {
                $initialPage = (int) $sessionProgress['current_page'];
            }
        }

        return view('reader', compact('book', 'pdfFile', 'streamUrl', 'canDownload', 'initialPage', 'bookmarks'));
    }

    /**
     * Securely stream PDF document binary to the reader.
     */
    public function stream(Request $request, string $slug)
    {
        $book = Book::with(['category', 'primaryPdf'])->where('slug', $slug)->firstOrFail();

        // Authorize stream request
        if (! $this->accessService->isStreamAuthorized($request, $book)) {
            abort(403, 'Unauthorized access to protected publication stream.');
        }

        // Release session lock early to allow parallel range chunk downloads
        if ($request->hasSession()) {
            $request->session()->save();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $file = $book->primaryPdf;
        $fullPath = null;

        if ($file) {
            if (Storage::disk($file->disk)->exists($file->file_path)) {
                $fullPath = Storage::disk($file->disk)->path($file->file_path);
            } elseif (file_exists(storage_path('app/'.$file->file_path))) {
                $fullPath = storage_path('app/'.$file->file_path);
            } elseif (file_exists(storage_path('app/private/'.$file->file_path))) {
                $fullPath = storage_path('app/private/'.$file->file_path);
            }
        }

        // If file missing on disk, generate mock and persist so it can be streamed via BinaryFileResponse with Range support
        if (! $fullPath || ! file_exists($fullPath)) {
            $pdfContent = SimplePdfBuilder::createSamplePdf(
                $book->title,
                $book->author_name,
                $book->category?->name ?? 'Curated',
                $book->description ?? 'Executive Leadership Edition'
            );

            $path = 'private/books/'.$book->slug.'.pdf';
            Storage::disk('local')->put($path, $pdfContent);
            $fullPath = Storage::disk('local')->path($path);

            $file = BookFile::updateOrCreate(
                ['book_id' => $book->id, 'file_type' => 'pdf'],
                [
                    'file_path' => $path,
                    'file_name' => $book->slug.'-executive.pdf',
                    'file_size' => strlen($pdfContent),
                    'file_hash' => hash('sha256', $pdfContent),
                    'disk' => 'local',
                    'page_count' => 3,
                    'is_active' => true,
                ]
            );
        }

        // Generate fast ETag: avoid heavy synchronous 6MB+ file re-hashing if file_hash is not pre-calculated
        $etag = $file?->file_hash
            ? '"'.$file->file_hash.'"'
            : '"'.md5($fullPath.filemtime($fullPath).filesize($fullPath)).'"';

        $response = response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($file?->file_name ?? basename($fullPath)).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=86400, stale-while-revalidate=3600',
            'ETag' => $etag,
        ]);

        $response->isNotModified($request);

        return $response;
    }
}
