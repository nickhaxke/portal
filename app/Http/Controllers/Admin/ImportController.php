<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportCalibreJob;
use App\Models\ImportBatch;
use App\Models\Library;
use App\Services\CalibreMigrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        protected CalibreMigrationService $migrationService
    ) {}

    public function index(): View
    {
        $batches = ImportBatch::with('user')->latest()->paginate(10);
        $totalImported = ImportBatch::sum('imported_count');
        $totalDuplicates = ImportBatch::sum('duplicate_count');
        $activeBatch = ImportBatch::where('status', 'processing')->latest()->first();
        $libraries = Library::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.imports.index', compact('batches', 'totalImported', 'totalDuplicates', 'activeBatch', 'libraries'));
    }

    public function scan(Request $request): View
    {
        $request->validate([
            'library_path' => ['required', 'string'],
        ]);

        $path = $request->input('library_path');
        $scanResult = $this->migrationService->scanLibrary($path);
        $libraries = Library::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.imports.scan', compact('scanResult', 'path', 'libraries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'library_path' => ['required', 'string'],
            'library_id' => ['nullable', 'exists:libraries,id'],
            'duplicate_mode' => ['nullable', 'in:skip,update,import_new'],
            'dry_run' => ['nullable', 'boolean'],
            'run_in_background' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $path = $validated['library_path'];
        $libraryId = ! empty($validated['library_id']) ? (int) $validated['library_id'] : null;
        $duplicateMode = $validated['duplicate_mode'] ?? 'skip';
        $dryRun = $request->boolean('dry_run');
        $runInBackground = $request->boolean('run_in_background');
        $limit = ! empty($validated['limit']) ? (int) $validated['limit'] : null;

        $validation = $this->migrationService->validateLibrary($path);
        if (! $validation['valid']) {
            return back()->with('error', $validation['error'])->withInput();
        }

        if ($runInBackground && ! $dryRun) {
            $batch = ImportBatch::create([
                'batch_code' => 'CALIBRE-'.date('Ymd-His').'-'.Str::upper(Str::random(4)),
                'user_id' => auth()->id(),
                'source_type' => 'calibre',
                'source_path' => $path,
                'total_detected' => $limit ?: ($validation['books_count'] ?? 0),
                'imported_count' => 0,
                'skipped_count' => 0,
                'duplicate_count' => 0,
                'failed_count' => 0,
                'status' => 'processing',
                'summary_report' => [
                    'logs' => [],
                    'queued_at' => now()->toDateTimeString(),
                    'duplicate_mode' => $duplicateMode,
                    'target_library_id' => $libraryId,
                ],
            ]);

            ImportCalibreJob::dispatch($path, $batch->id, $limit, $libraryId, $duplicateMode);

            return redirect()->route('admin.imports.show', $batch->id)
                ->with('success', "Calibre import batch '{$batch->batch_code}' successfully queued for background processing.");
        }

        $batch = $this->migrationService->importLibrary($path, 50, null, $dryRun, $limit, $libraryId, $duplicateMode);

        return redirect()->route('admin.imports.show', $batch->id)
            ->with('success', "Calibre import batch '{$batch->batch_code}' successfully processed: {$batch->imported_count} imported/updated, {$batch->duplicate_count} duplicates skipped.");
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'library_id' => ['nullable', 'exists:libraries,id'],
            'duplicate_mode' => ['nullable', 'in:skip,update,import_new'],
        ]);

        $file = $request->file('csv_file');
        $tempPath = $file->getRealPath();
        $libraryId = ! empty($validated['library_id']) ? (int) $validated['library_id'] : null;
        $duplicateMode = $validated['duplicate_mode'] ?? 'skip';

        try {
            $batch = $this->migrationService->importCsv($tempPath, $libraryId, $duplicateMode);

            return redirect()->route('admin.imports.show', $batch->id)
                ->with('success', "CSV Import batch '{$batch->batch_code}' successfully processed: {$batch->imported_count} imported/updated, {$batch->duplicate_count} duplicates skipped.");
        } catch (\Throwable $e) {
            return back()->with('error', 'CSV Import failed: '.$e->getMessage());
        }
    }

    public function show(ImportBatch $import): View
    {
        $import->load('user');

        return view('admin.imports.show', compact('import'));
    }
}
