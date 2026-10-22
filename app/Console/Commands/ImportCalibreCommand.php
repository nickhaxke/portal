<?php

namespace App\Console\Commands;

use App\Services\CalibreMigrationService;
use Illuminate\Console\Command;

class ImportCalibreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal:import-calibre 
                            {path : The absolute or relative path to the Calibre library folder}
                            {--dry-run : Scan and simulate the migration without creating records}
                            {--limit= : Limit the number of publications to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import publications, authors, identifiers and PDFs from a Calibre SQLite metadata.db library';

    /**
     * Execute the console command.
     */
    public function handle(CalibreMigrationService $migrationService): int
    {
        $path = $this->argument('path');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info("Scanning Calibre library at: {$path}");

        $validation = $migrationService->validateLibrary($path);
        if (! $validation['valid']) {
            $this->error('Validation failed: '.$validation['error']);

            return Command::FAILURE;
        }

        $this->info("Found {$validation['books_count']} books, {$validation['authors_count']} authors in library.");

        if ($dryRun) {
            $this->warn('Executing in DRY-RUN mode. No changes will be written to the database or storage.');
        }

        $this->output->progressStart($limit ? min($limit, $validation['books_count']) : $validation['books_count']);

        $batch = $migrationService->importLibrary($path, 50, null, $dryRun, $limit);

        $this->output->progressFinish();

        $this->table(
            ['Batch Code', 'Status', 'Imported', 'Duplicates Skipped', 'Failed'],
            [[
                $batch->batch_code,
                $batch->status,
                $batch->imported_count,
                $batch->duplicate_count,
                $batch->failed_count,
            ]]
        );

        $this->info("Import process complete. Batch code: {$batch->batch_code}");

        return Command::SUCCESS;
    }
}
