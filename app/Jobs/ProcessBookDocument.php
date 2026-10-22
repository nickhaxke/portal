<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\BookFile;
use App\Services\PdfMetadataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBookDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $bookId,
        public int $bookFileId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PdfMetadataService $pdfService): void
    {
        $book = Book::find($this->bookId);
        $file = BookFile::find($this->bookFileId);

        if (! $book || ! $file) {
            return;
        }

        $fullPath = null;
        if (Storage::disk($file->disk)->exists($file->file_path)) {
            $fullPath = Storage::disk($file->disk)->path($file->file_path);
        } elseif (file_exists(storage_path('app/'.$file->file_path))) {
            $fullPath = storage_path('app/'.$file->file_path);
        }

        if (! $fullPath || ! file_exists($fullPath)) {
            Log::warning("ProcessBookDocument: Physical file not found for Book #{$this->bookId}, File #{$this->bookFileId}");

            return;
        }

        try {
            $metadata = $pdfService->extract($fullPath);

            $fileUpdates = [];
            if (! empty($metadata['file_hash'])) {
                $fileUpdates['file_hash'] = $metadata['file_hash'];
            }
            if (! empty($metadata['file_size'])) {
                $fileUpdates['file_size'] = $metadata['file_size'];
            }
            if (! empty($metadata['page_count']) && $metadata['page_count'] > 0) {
                $fileUpdates['page_count'] = $metadata['page_count'];
            }

            if (! empty($fileUpdates)) {
                $file->update($fileUpdates);
            }

            // Update book page count if accurate count is found and book had placeholder 1
            if (! empty($metadata['page_count']) && $metadata['page_count'] > 1 && $book->pages_count <= 1) {
                $book->update(['pages_count' => $metadata['page_count']]);
            }

            Log::info("ProcessBookDocument: Processed Book #{$book->id} ({$book->title}): {$metadata['page_count']} pages, hash {$metadata['file_hash']}");
        } catch (\Throwable $e) {
            Log::error("ProcessBookDocument failed for Book #{$book->id}: ".$e->getMessage());
        }
    }
}
