<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\CalibreMigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportCalibreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for long-running imports in seconds.
     */
    public int $timeout = 600;

    /**
     * Number of attempts.
     */
    public int $tries = 1;

    public function __construct(
        public string $calibrePath,
        public int $batchId,
        public ?int $limit = null,
        public ?int $targetLibraryId = null,
        public string $duplicateMode = 'skip'
    ) {}

    public function handle(CalibreMigrationService $service): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (! $batch) {
            Log::error("ImportCalibreJob: Batch #{$this->batchId} not found.");

            return;
        }

        try {
            $service->importLibrary(
                $this->calibrePath,
                50,
                $batch,
                false,
                $this->limit,
                $this->targetLibraryId,
                $this->duplicateMode
            );
            Log::info("ImportCalibreJob: Batch #{$batch->batch_code} completed successfully.");
        } catch (\Throwable $e) {
            Log::error("ImportCalibreJob failed for Batch #{$batch->id}: ".$e->getMessage());
            $batch->update([
                'status' => 'failed',
                'summary_report' => array_merge($batch->summary_report ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toDateTimeString(),
                ]),
            ]);
        }
    }
}
