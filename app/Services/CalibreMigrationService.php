<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Library;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;

class CalibreMigrationService
{
    /**
     * Validate whether the directory contains a readable Calibre library.
     */
    public function validateLibrary(string $calibrePath): array
    {
        $calibrePath = rtrim($calibrePath, '/\\');
        $dbPath = $calibrePath.DIRECTORY_SEPARATOR.'metadata.db';

        if (! File::exists($calibrePath) || ! File::isDirectory($calibrePath)) {
            return [
                'valid' => false,
                'error' => "The path '{$calibrePath}' does not exist or is not a directory.",
            ];
        }

        if (! File::exists($dbPath)) {
            return [
                'valid' => false,
                'error' => "Calibre SQLite database 'metadata.db' was not found in '{$calibrePath}'.",
            ];
        }

        try {
            $pdo = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Test if standard Calibre tables exist
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            $requiredTables = ['books', 'authors', 'data'];
            $missing = array_diff($requiredTables, $tables);

            if (! empty($missing)) {
                return [
                    'valid' => false,
                    'error' => 'The database is missing standard Calibre tables: '.implode(', ', $missing),
                ];
            }

            $booksCount = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
            $authorsCount = (int) $pdo->query('SELECT COUNT(*) FROM authors')->fetchColumn();
            $formatsCount = (int) $pdo->query('SELECT COUNT(*) FROM data')->fetchColumn();

            return [
                'valid' => true,
                'error' => null,
                'db_path' => $dbPath,
                'books_count' => $booksCount,
                'authors_count' => $authorsCount,
                'formats_count' => $formatsCount,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Could not read Calibre SQLite database: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Scan library and provide pre-import summary report including duplicate detection.
     */
    public function scanLibrary(string $calibrePath): array
    {
        $validation = $this->validateLibrary($calibrePath);
        if (! $validation['valid']) {
            return $validation;
        }

        $calibrePath = rtrim($calibrePath, '/\\');
        $dbPath = $calibrePath.DIRECTORY_SEPARATOR.'metadata.db';
        $pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Formats breakdown
        $formatStmt = $pdo->query('SELECT format, COUNT(*) as count FROM data GROUP BY format ORDER BY count DESC');
        $formats = $formatStmt->fetchAll();

        // Sample books with files
        $booksStmt = $pdo->query('
            SELECT b.id, b.title, b.path, b.has_cover, b.pubdate,
                   GROUP_CONCAT(DISTINCT a.name) as author_names
            FROM books b
            LEFT JOIN books_authors_link bal ON bal.book = b.id
            LEFT JOIN authors a ON a.id = bal.author
            GROUP BY b.id
            ORDER BY b.id ASC
            LIMIT 50
        ');
        $sampleBooks = $booksStmt->fetchAll();

        $existingCalibreIds = Book::whereNotNull('calibre_id')->pluck('calibre_id')->toArray();
        $existingHashes = BookFile::whereNotNull('file_hash')->pluck('file_hash')->toArray();

        $potentialDuplicates = 0;
        $missingFiles = 0;
        $previewItems = [];

        foreach ($sampleBooks as $book) {
            $isDuplicate = in_array((int) $book['id'], $existingCalibreIds, true);
            if ($isDuplicate) {
                $potentialDuplicates++;
            }

            // Check if directory exists
            $bookDir = $calibrePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $book['path']);
            $hasFilesOnDisk = File::isDirectory($bookDir);
            if (! $hasFilesOnDisk) {
                $missingFiles++;
            }

            $previewItems[] = [
                'id' => $book['id'],
                'title' => $book['title'],
                'author' => $book['author_names'] ?? 'Unknown Author',
                'has_cover' => (bool) $book['has_cover'],
                'exists_in_portal' => $isDuplicate,
                'files_found_on_disk' => $hasFilesOnDisk,
            ];
        }

        return [
            'valid' => true,
            'books_count' => $validation['books_count'],
            'authors_count' => $validation['authors_count'],
            'formats' => $formats,
            'sample_books' => $previewItems,
            'potential_duplicates_in_sample' => $potentialDuplicates,
            'missing_files_in_sample' => $missingFiles,
        ];
    }

    /**
     * Import books from a Calibre library into the portal.
     * Supports duplicate resolution (skip, update, import_new) and library assignment.
     */
    public function importLibrary(
        string $calibrePath,
        int $batchSize = 100,
        ?ImportBatch $batch = null,
        bool $dryRun = false,
        ?int $limit = null,
        ?int $targetLibraryId = null,
        string $duplicateMode = 'skip'
    ): ImportBatch {
        $calibrePath = rtrim($calibrePath, '/\\');
        $dbPath = $calibrePath.DIRECTORY_SEPARATOR.'metadata.db';

        $pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if (! $batch) {
            $totalCount = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
            if ($limit && $limit < $totalCount) {
                $totalCount = $limit;
            }

            $batch = ImportBatch::create([
                'batch_code' => 'CALIBRE-'.date('Ymd-His').'-'.Str::upper(Str::random(4)),
                'user_id' => auth()->id(),
                'source_type' => 'calibre',
                'source_path' => $calibrePath,
                'total_detected' => $totalCount,
                'imported_count' => 0,
                'skipped_count' => 0,
                'duplicate_count' => 0,
                'failed_count' => 0,
                'status' => 'processing',
                'summary_report' => [
                    'logs' => [],
                    'started_at' => now()->toDateTimeString(),
                    'duplicate_mode' => $duplicateMode,
                    'target_library_id' => $targetLibraryId,
                ],
            ]);
        } else {
            $batch->update(['status' => 'processing']);
        }

        $defaultCategory = Category::firstOrCreate(
            ['slug' => 'general-executive'],
            ['name' => 'General Leadership', 'icon' => 'strategy', 'description' => 'Migrated Calibre Publications']
        );

        $targetLibrary = $targetLibraryId ? Library::find($targetLibraryId) : Library::first();
        $assignedLibraryId = $targetLibrary?->id;

        // Detect available Calibre tables
        $availableTables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $hasSeriesTable = in_array('series', $availableTables, true) && in_array('books_series_link', $availableTables, true);
        $hasTagsTable = in_array('tags', $availableTables, true) && in_array('books_tags_link', $availableTables, true);
        $hasLanguagesTable = in_array('languages', $availableTables, true) && in_array('books_languages_link', $availableTables, true);

        // Detect columns in books table
        $bookCols = $pdo->query('PRAGMA table_info(books)')->fetchAll(PDO::FETCH_COLUMN, 1);
        $hasSeriesIndex = in_array('series_index', $bookCols, true);
        $seriesColSql = $hasSeriesIndex ? 'b.series_index,' : '';

        $limitSql = $limit ? "LIMIT {$limit}" : '';
        $booksQuery = $pdo->query("
            SELECT b.id, b.title, b.sort, b.pubdate, {$seriesColSql} b.path, b.has_cover
            FROM books b
            ORDER BY b.id ASC
            {$limitSql}
        ");

        $logs = $batch->summary_report['logs'] ?? [];
        $imported = $batch->imported_count;
        $skipped = $batch->skipped_count;
        $duplicates = $batch->duplicate_count;
        $failed = $batch->failed_count;

        while ($calibreBook = $booksQuery->fetch()) {
            $bookId = (int) $calibreBook['id'];
            $title = trim($calibreBook['title']);

            try {
                // 1. Duplicate & Idempotency Check by calibre_id
                $existingBook = Book::where('calibre_id', $bookId)->first();
                if ($existingBook && $duplicateMode === 'skip') {
                    $duplicates++;
                    $logs[] = [
                        'status' => 'duplicate',
                        'calibre_id' => $bookId,
                        'title' => $title,
                        'message' => "Book already imported with Portal ID #{$existingBook->id}. Skipped.",
                    ];

                    continue;
                }

                if ($dryRun) {
                    $imported++;
                    $logs[] = [
                        'status' => 'dry_run_ready',
                        'calibre_id' => $bookId,
                        'title' => $title,
                    ];

                    continue;
                }

                // 2. Fetch Authors
                $authorStmt = $pdo->prepare('
                    SELECT a.id, a.name, a.sort
                    FROM authors a
                    JOIN books_authors_link bal ON bal.author = a.id
                    WHERE bal.book = :book_id
                    ORDER BY bal.id ASC
                ');
                $authorStmt->execute([':book_id' => $bookId]);
                $calibreAuthors = $authorStmt->fetchAll();

                $primaryAuthorName = ! empty($calibreAuthors) ? trim($calibreAuthors[0]['name']) : 'Unknown Author';
                $primaryAuthor = Author::firstOrCreate(
                    ['slug' => Str::slug($primaryAuthorName)],
                    ['name' => $primaryAuthorName]
                );

                // 3. Fetch Identifiers (ISBN, DOI)
                $isbn = null;
                $doi = null;
                $identStmt = $pdo->prepare('SELECT type, val FROM identifiers WHERE book = :book_id');
                $identStmt->execute([':book_id' => $bookId]);
                while ($row = $identStmt->fetch()) {
                    if (strtolower($row['type']) === 'isbn') {
                        $isbn = substr(trim($row['val']), 0, 50);
                    } elseif (strtolower($row['type']) === 'doi') {
                        $doi = substr(trim($row['val']), 0, 100);
                    }
                }

                // 4. Fetch Publisher
                $publisher = null;
                $pubStmt = $pdo->prepare('
                    SELECT p.name FROM publishers p
                    JOIN books_publishers_link bpl ON bpl.publisher = p.id
                    WHERE bpl.book = :book_id LIMIT 1
                ');
                $pubStmt->execute([':book_id' => $bookId]);
                $pubRow = $pubStmt->fetch();
                if ($pubRow) {
                    $publisher = substr(trim($pubRow['name']), 0, 191);
                }

                // 5. Fetch Description / Comments
                $description = null;
                $commentStmt = $pdo->prepare('SELECT text FROM comments WHERE book = :book_id LIMIT 1');
                $commentStmt->execute([':book_id' => $bookId]);
                $commentRow = $commentStmt->fetch();
                if ($commentRow) {
                    $description = strip_tags(trim($commentRow['text']));
                }

                // 6. Publication year
                $publishedYear = date('Y');
                if (! empty($calibreBook['pubdate'])) {
                    $parsedYear = (int) substr($calibreBook['pubdate'], 0, 4);
                    if ($parsedYear > 1800 && $parsedYear < 2100) {
                        $publishedYear = $parsedYear;
                    }
                }

                // 7. Series & Series Number
                $series = null;
                $seriesNumber = null;
                if ($hasSeriesTable) {
                    $seriesStmt = $pdo->prepare('
                        SELECT s.name FROM series s
                        JOIN books_series_link bsl ON bsl.series = s.id
                        WHERE bsl.book = :book_id LIMIT 1
                    ');
                    $seriesStmt->execute([':book_id' => $bookId]);
                    $seriesRow = $seriesStmt->fetch();
                    if ($seriesRow) {
                        $series = substr(trim($seriesRow['name']), 0, 191);
                        $seriesNumber = isset($calibreBook['series_index']) ? (float) $calibreBook['series_index'] : 1.0;
                    }
                }

                // 8. Tags
                $tagNames = [];
                if ($hasTagsTable) {
                    $tagStmt = $pdo->prepare('
                        SELECT t.name FROM tags t
                        JOIN books_tags_link btl ON btl.tag = t.id
                        WHERE btl.book = :book_id
                    ');
                    $tagStmt->execute([':book_id' => $bookId]);
                    while ($tagRow = $tagStmt->fetch()) {
                        $t = trim($tagRow['name']);
                        if ($t !== '') {
                            $tagNames[] = substr($t, 0, 50);
                        }
                    }
                }

                // 9. Language
                $language = 'en';
                if ($hasLanguagesTable) {
                    $langStmt = $pdo->prepare('
                        SELECT l.lang_code FROM languages l
                        JOIN books_languages_link bll ON bll.lang_code = l.id
                        WHERE bll.book = :book_id LIMIT 1
                    ');
                    $langStmt->execute([':book_id' => $bookId]);
                    $langRow = $langStmt->fetch();
                    if ($langRow && ! empty($langRow['lang_code'])) {
                        $language = strtolower(substr(trim($langRow['lang_code']), 0, 20));
                    }
                }

                // 10. Base book slug
                $slug = Str::slug($title);
                if (empty($slug)) {
                    $slug = 'calibre-book-'.$bookId;
                }
                $count = Book::where('slug', 'like', "{$slug}%")->count();
                if ($count > 0 && (! $existingBook || $duplicateMode === 'import_new')) {
                    $slug .= '-'.($count + 1);
                }

                // 11. Handle Cover Image
                $coverImagePath = null;
                if ($calibreBook['has_cover']) {
                    $sourceCover = $calibrePath.DIRECTORY_SEPARATOR.
                        str_replace('/', DIRECTORY_SEPARATOR, $calibreBook['path']).
                        DIRECTORY_SEPARATOR.'cover.jpg';

                    if (File::exists($sourceCover)) {
                        $destRelative = 'covers/calibre_'.$bookId.'_'.Str::slug($title).'.jpg';
                        Storage::disk('public')->put($destRelative, File::get($sourceCover));
                        $coverImagePath = $destRelative;
                    }
                }

                // 12. Process Files (PDF, EPUB) and SHA-256 Duplication
                $dataStmt = $pdo->prepare('
                    SELECT id, format, uncompressed_size, name
                    FROM data WHERE book = :book_id
                ');
                $dataStmt->execute([':book_id' => $bookId]);
                $filesData = $dataStmt->fetchAll();

                $filesToAttach = [];

                foreach ($filesData as $f) {
                    $ext = strtolower($f['format']);
                    $fileName = $f['name'].'.'.$ext;
                    $sourceFilePath = $calibrePath.DIRECTORY_SEPARATOR.
                        str_replace('/', DIRECTORY_SEPARATOR, $calibreBook['path']).
                        DIRECTORY_SEPARATOR.$fileName;

                    if (File::exists($sourceFilePath)) {
                        $hash = hash_file('sha256', $sourceFilePath);
                        $destPrivatePath = 'private/books/'.$slug.'.'.$ext;
                        Storage::disk('local')->put($destPrivatePath, File::get($sourceFilePath));

                        $filesToAttach[] = [
                            'path' => $destPrivatePath,
                            'name' => $fileName,
                            'size' => filesize($sourceFilePath),
                            'type' => $ext,
                            'hash' => $hash,
                        ];
                    }
                }

                if (empty($filesToAttach) && empty($filesData)) {
                    $samplePdf = SimplePdfBuilder::createSamplePdf(
                        $title,
                        $primaryAuthorName,
                        'Migrated Calibre Book',
                        $description ?? 'Migrated from Calibre library.'
                    );
                    $destPrivatePath = 'private/books/'.$slug.'.pdf';
                    Storage::disk('local')->put($destPrivatePath, $samplePdf);

                    $filesToAttach[] = [
                        'path' => $destPrivatePath,
                        'name' => $slug.'.pdf',
                        'size' => strlen($samplePdf),
                        'type' => 'pdf',
                        'hash' => hash('sha256', $samplePdf),
                    ];
                }

                // 13. Update or Create Book Record inside DB Transaction
                if ($existingBook && $duplicateMode === 'update') {
                    DB::transaction(function () use (
                        $existingBook,
                        $title,
                        $primaryAuthor,
                        $primaryAuthorName,
                        $publisher,
                        $isbn,
                        $doi,
                        $publishedYear,
                        $description,
                        $coverImagePath,
                        $filesToAttach,
                        $assignedLibraryId,
                        $series,
                        $seriesNumber,
                        $tagNames,
                        $language
                    ) {
                        $existingBook->update([
                            'title' => $title,
                            'library_id' => $assignedLibraryId ?: $existingBook->library_id,
                            'author_id' => $primaryAuthor->id,
                            'author_name' => $primaryAuthorName,
                            'publisher' => $publisher ?: $existingBook->publisher,
                            'isbn' => $isbn ?: $existingBook->isbn,
                            'doi' => $doi ?: $existingBook->doi,
                            'series' => $series ?: $existingBook->series,
                            'series_number' => $seriesNumber ?: $existingBook->series_number,
                            'language' => $language ?: $existingBook->language,
                            'publication_year' => $publishedYear ?: $existingBook->publication_year,
                            'description' => $description ?: $existingBook->description,
                            'cover_image' => $coverImagePath ?: $existingBook->cover_image,
                        ]);

                        if (! empty($tagNames)) {
                            $existingBook->syncTags($tagNames);
                        }

                        foreach ($filesToAttach as $fa) {
                            $file = BookFile::where('book_id', $existingBook->id)
                                ->where('file_type', $fa['type'])
                                ->first();
                            if (! $file) {
                                BookFile::create([
                                    'book_id' => $existingBook->id,
                                    'file_path' => $fa['path'],
                                    'file_name' => $fa['name'],
                                    'file_size' => $fa['size'],
                                    'file_type' => $fa['type'],
                                    'file_hash' => $fa['hash'],
                                    'version' => 1,
                                    'disk' => 'local',
                                    'page_count' => 1,
                                    'is_active' => true,
                                ]);
                            }
                        }
                    });

                    $imported++;
                    $logs[] = [
                        'status' => 'updated',
                        'calibre_id' => $bookId,
                        'title' => $title,
                        'message' => "Book #{$existingBook->id} successfully updated with latest Calibre metadata.",
                    ];
                } else {
                    DB::transaction(function () use (
                        $bookId,
                        $title,
                        $slug,
                        $primaryAuthor,
                        $primaryAuthorName,
                        $defaultCategory,
                        $publisher,
                        $isbn,
                        $doi,
                        $publishedYear,
                        $description,
                        $coverImagePath,
                        $filesToAttach,
                        $calibreAuthors,
                        $assignedLibraryId,
                        $series,
                        $seriesNumber,
                        $tagNames,
                        $language
                    ) {
                        $book = Book::create([
                            'library_id' => $assignedLibraryId,
                            'title' => $title,
                            'slug' => $slug,
                            'publication_type' => 'book',
                            'calibre_id' => $bookId,
                            'author_id' => $primaryAuthor->id,
                            'author_name' => $primaryAuthorName,
                            'category_id' => $defaultCategory->id,
                            'publisher' => $publisher,
                            'isbn' => $isbn,
                            'doi' => $doi,
                            'series' => $series,
                            'series_number' => $seriesNumber,
                            'language' => $language,
                            'publication_year' => $publishedYear,
                            'published_year' => $publishedYear,
                            'description' => $description,
                            'cover_image' => $coverImagePath,
                            'cover_bg' => '#102946',
                            'cover_fg' => '#ffffff',
                            'status' => 'published',
                            'allow_download' => false,
                            'published_at' => now(),
                        ]);

                        if (! empty($tagNames)) {
                            $book->syncTags($tagNames);
                        }

                        foreach ($calibreAuthors as $idx => $ca) {
                            $auth = Author::firstOrCreate(
                                ['slug' => Str::slug($ca['name'])],
                                ['name' => trim($ca['name'])]
                            );
                            $book->authors()->syncWithoutDetaching([
                                $auth->id => ['role' => 'author', 'sort_order' => $idx + 1],
                            ]);
                        }

                        foreach ($filesToAttach as $index => $fa) {
                            BookFile::create([
                                'book_id' => $book->id,
                                'file_path' => $fa['path'],
                                'file_name' => $fa['name'],
                                'file_size' => $fa['size'],
                                'file_type' => $fa['type'],
                                'file_hash' => $fa['hash'],
                                'version' => 1,
                                'disk' => 'local',
                                'page_count' => 1,
                                'is_active' => $index === 0,
                            ]);
                        }
                    });

                    $imported++;
                    $logs[] = [
                        'status' => 'imported',
                        'calibre_id' => $bookId,
                        'title' => $title,
                        'files_count' => count($filesToAttach),
                    ];
                }
            } catch (Exception $e) {
                $failed++;
                $logs[] = [
                    'status' => 'failed',
                    'calibre_id' => $bookId,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ];
            }

            // Update batch progress periodically
            if (($imported + $failed + $duplicates) % 25 === 0) {
                $batch->update([
                    'imported_count' => $imported,
                    'duplicate_count' => $duplicates,
                    'failed_count' => $failed,
                    'summary_report' => array_merge($batch->summary_report ?? [], [
                        'logs' => array_slice($logs, -100),
                        'last_updated_at' => now()->toDateTimeString(),
                    ]),
                ]);
            }
        }

        $batch->update([
            'imported_count' => $imported,
            'duplicate_count' => $duplicates,
            'failed_count' => $failed,
            'status' => $failed > 0 && $imported === 0 ? 'failed' : 'completed',
            'summary_report' => [
                'logs' => array_slice($logs, -200),
                'completed_at' => now()->toDateTimeString(),
            ],
        ]);

        AuditLogService::log(
            'calibre.import_completed',
            $batch,
            null,
            [
                'batch_code' => $batch->batch_code,
                'imported' => $imported,
                'duplicates' => $duplicates,
                'failed' => $failed,
                'library_id' => $assignedLibraryId,
                'duplicate_mode' => $duplicateMode,
            ]
        );

        return $batch;
    }

    /**
     * Import books from a structured CSV file as secondary import method.
     */
    public function importCsv(
        string $csvPath,
        ?int $targetLibraryId = null,
        string $duplicateMode = 'skip',
        ?ImportBatch $batch = null
    ): ImportBatch {
        if (! file_exists($csvPath) || ! is_readable($csvPath)) {
            throw new Exception("CSV file not found or not readable: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        if (! $handle) {
            throw new Exception("Unable to open CSV file: {$csvPath}");
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            throw new Exception('CSV file is empty.');
        }

        // Map column indices
        $cleanHeader = array_map(fn ($h) => strtolower(trim(str_replace([' ', '_', '-'], '', $h))), $header);
        $titleIdx = array_search('title', $cleanHeader);
        if ($titleIdx === false) {
            fclose($handle);
            throw new Exception("CSV must contain a 'title' column.");
        }

        $authorIdx = array_search('author', $cleanHeader);
        if ($authorIdx === false) {
            $authorIdx = array_search('authorname', $cleanHeader);
        }
        $publisherIdx = array_search('publisher', $cleanHeader);
        $isbnIdx = array_search('isbn', $cleanHeader);
        $yearIdx = array_search('year', $cleanHeader);
        if ($yearIdx === false) {
            $yearIdx = array_search('publicationyear', $cleanHeader);
        }
        $seriesIdx = array_search('series', $cleanHeader);
        $seriesNumIdx = array_search('seriesnumber', $cleanHeader);
        if ($seriesNumIdx === false) {
            $seriesNumIdx = array_search('seriesindex', $cleanHeader);
        }
        $descIdx = array_search('description', $cleanHeader);
        if ($descIdx === false) {
            $descIdx = array_search('summary', $cleanHeader);
        }
        $categoryIdx = array_search('category', $cleanHeader);
        if ($categoryIdx === false) {
            $categoryIdx = array_search('discipline', $cleanHeader);
        }
        $tagsIdx = array_search('tags', $cleanHeader);
        $langIdx = array_search('language', $cleanHeader);

        if (! $batch) {
            $batch = ImportBatch::create([
                'batch_code' => 'CSV-'.date('Ymd-His').'-'.Str::upper(Str::random(4)),
                'user_id' => auth()->id(),
                'source_type' => 'csv',
                'source_path' => $csvPath,
                'total_detected' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'duplicate_count' => 0,
                'failed_count' => 0,
                'status' => 'processing',
                'summary_report' => [
                    'logs' => [],
                    'started_at' => now()->toDateTimeString(),
                    'duplicate_mode' => $duplicateMode,
                    'target_library_id' => $targetLibraryId,
                ],
            ]);
        }

        $targetLibrary = $targetLibraryId ? Library::find($targetLibraryId) : Library::first();
        $assignedLibraryId = $targetLibrary?->id;

        $defaultCategory = Category::firstOrCreate(
            ['slug' => 'general-executive'],
            ['name' => 'General Leadership', 'icon' => 'strategy']
        );

        $imported = 0;
        $duplicates = 0;
        $failed = 0;
        $totalDetected = 0;
        $logs = [];

        while (($row = fgetcsv($handle)) !== false) {
            $totalDetected++;
            $title = trim($row[$titleIdx] ?? '');
            if (empty($title)) {
                continue;
            }

            $authorName = $authorIdx !== false ? trim($row[$authorIdx] ?? 'Unknown Author') : 'Unknown Author';
            $publisher = $publisherIdx !== false ? trim($row[$publisherIdx] ?? '') : null;
            $isbn = $isbnIdx !== false ? trim($row[$isbnIdx] ?? '') : null;
            $year = $yearIdx !== false ? (int) trim($row[$yearIdx] ?? date('Y')) : (int) date('Y');
            $series = $seriesIdx !== false ? trim($row[$seriesIdx] ?? '') : null;
            $seriesNum = $seriesNumIdx !== false ? (float) trim($row[$seriesNumIdx] ?? '1.0') : null;
            $desc = $descIdx !== false ? trim($row[$descIdx] ?? '') : null;
            $categoryName = $categoryIdx !== false ? trim($row[$categoryIdx] ?? '') : null;
            $tagInput = $tagsIdx !== false ? trim($row[$tagsIdx] ?? '') : null;
            $lang = $langIdx !== false ? trim($row[$langIdx] ?? 'en') : 'en';

            try {
                // Check duplicate by ISBN or exact Title + Author
                $existing = Book::where(function ($q) use ($isbn, $title, $authorName) {
                    if (! empty($isbn)) {
                        $q->where('isbn', $isbn);
                    }
                    if (! empty($title) && ! empty($authorName)) {
                        $q->orWhere(function ($sub) use ($title, $authorName) {
                            $sub->where('title', $title)->where('author_name', $authorName);
                        });
                    }
                })->first();

                if ($existing && $duplicateMode === 'skip') {
                    $duplicates++;
                    $logs[] = [
                        'status' => 'duplicate',
                        'title' => $title,
                        'message' => "Book already exists (ID #{$existing->id}). Skipped.",
                    ];

                    continue;
                }

                $category = $defaultCategory;
                if (! empty($categoryName)) {
                    $category = Category::firstOrCreate(
                        ['slug' => Str::slug($categoryName)],
                        ['name' => $categoryName, 'icon' => 'strategy']
                    );
                }

                $author = Author::firstOrCreate(
                    ['slug' => Str::slug($authorName)],
                    ['name' => $authorName]
                );

                if ($existing && $duplicateMode === 'update') {
                    $existing->update([
                        'title' => $title,
                        'library_id' => $assignedLibraryId ?: $existing->library_id,
                        'author_id' => $author->id,
                        'author_name' => $authorName,
                        'publisher' => $publisher ?: $existing->publisher,
                        'isbn' => $isbn ?: $existing->isbn,
                        'series' => $series ?: $existing->series,
                        'series_number' => $seriesNum ?: $existing->series_number,
                        'publication_year' => $year ?: $existing->publication_year,
                        'description' => $desc ?: $existing->description,
                        'language' => $lang ?: $existing->language,
                    ]);
                    if (! empty($tagInput)) {
                        $existing->syncTags($tagInput);
                    }
                    $imported++;
                    $logs[] = ['status' => 'updated', 'title' => $title, 'id' => $existing->id];
                } else {
                    $slug = Str::slug($title);
                    $count = Book::where('slug', 'like', "{$slug}%")->count();
                    if ($count > 0) {
                        $slug .= '-'.($count + 1);
                    }

                    $book = Book::create([
                        'library_id' => $assignedLibraryId,
                        'title' => $title,
                        'slug' => $slug,
                        'publication_type' => 'book',
                        'author_id' => $author->id,
                        'author_name' => $authorName,
                        'category_id' => $category->id,
                        'publisher' => $publisher,
                        'isbn' => $isbn,
                        'publication_year' => $year,
                        'published_year' => $year,
                        'series' => $series,
                        'series_number' => $seriesNum,
                        'language' => $lang,
                        'description' => $desc,
                        'status' => 'published',
                        'allow_download' => false,
                        'published_at' => now(),
                    ]);

                    if (! empty($tagInput)) {
                        $book->syncTags($tagInput);
                    }

                    // Attach placeholder sample PDF
                    $samplePdf = SimplePdfBuilder::createSamplePdf(
                        $title,
                        $authorName,
                        $category->name,
                        $desc ?? 'Imported Publication'
                    );
                    $destPath = 'private/books/'.$slug.'.pdf';
                    Storage::disk('local')->put($destPath, $samplePdf);

                    BookFile::create([
                        'book_id' => $book->id,
                        'file_path' => $destPath,
                        'file_name' => $slug.'.pdf',
                        'file_size' => strlen($samplePdf),
                        'file_type' => 'pdf',
                        'file_hash' => hash('sha256', $samplePdf),
                        'version' => 1,
                        'disk' => 'local',
                        'page_count' => 1,
                        'is_active' => true,
                    ]);

                    $imported++;
                    $logs[] = ['status' => 'imported', 'title' => $title, 'id' => $book->id];
                }
            } catch (Exception $e) {
                $failed++;
                $logs[] = ['status' => 'failed', 'title' => $title, 'error' => $e->getMessage()];
            }
        }

        fclose($handle);

        $batch->update([
            'total_detected' => $totalDetected,
            'imported_count' => $imported,
            'duplicate_count' => $duplicates,
            'failed_count' => $failed,
            'status' => $failed > 0 && $imported === 0 ? 'failed' : 'completed',
            'summary_report' => [
                'logs' => array_slice($logs, -200),
                'completed_at' => now()->toDateTimeString(),
            ],
        ]);

        return $batch;
    }
}
