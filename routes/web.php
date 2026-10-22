<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\InsightController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\ReaderAdminController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\ReadingProgressController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Executive Portal Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/explore', [CatalogueController::class, 'explore'])->name('explore');
Route::get('/libraries', [CatalogueController::class, 'libraries'])->name('libraries.index');
Route::get('/libraries/{slug}', [CatalogueController::class, 'libraryShow'])->name('libraries.show');
Route::get('/categories', [CatalogueController::class, 'categories'])->name('categories.index');
Route::get('/categories/{slug}', [CatalogueController::class, 'category'])->name('categories.show');
Route::get('/books/{slug}', [CatalogueController::class, 'book'])->name('books.show');
Route::post('/books/{book}/toggle-save', [CatalogueController::class, 'toggleSave'])->name('books.toggle-save');
Route::get('/my-library', [CatalogueController::class, 'library'])->name('library');

Route::get('/insights', [CatalogueController::class, 'insights'])->name('insights.index');
Route::get('/insights/{slug}', [CatalogueController::class, 'insight'])->name('insights.show');

// PDF Online Reader, Stream & Progress Routes
Route::get('/books/{slug}/reader', [ReaderController::class, 'read'])->name('books.reader');
Route::get('/books/{slug}/stream-pdf', [ReaderController::class, 'stream'])->name('books.stream-pdf');
Route::post('/books/{slug}/progress', [ReadingProgressController::class, 'updateProgress'])->name('books.progress');
Route::post('/books/{slug}/bookmark', [ReadingProgressController::class, 'addBookmark'])->name('books.bookmark');
Route::delete('/books/{slug}/bookmark/{bookmark}', [ReadingProgressController::class, 'removeBookmark'])->name('books.bookmark.remove');

// Storage fallback route when webserver public/storage symlink is missing on live hosting
Route::get('/storage/{path}', [MediaController::class, 'servePublicStorage'])->where('path', '.*')->name('storage.fallback');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Reader Registration
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Password Recovery
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Email Verification
Route::get('/email/verify', [VerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', [VerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Aliases for /admin/login and /admin/logout
Route::get('/admin/login', fn () => redirect()->route('login'));
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| Executive Administration Control Center Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    // 1. Overview Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Library & Publications
    Route::post('libraries/{library}/assign-books', [LibraryController::class, 'assignBooks'])->name('libraries.assign-books');
    Route::post('libraries/{library}/remove-book/{book}', [LibraryController::class, 'removeBook'])->name('libraries.remove-book');
    Route::resource('libraries', LibraryController::class);

    Route::post('books/bulk', [BookController::class, 'bulkAction'])->name('books.bulk');
    Route::post('books/{id}/restore', [BookController::class, 'restore'])->name('books.restore');
    Route::delete('books/{id}/force', [BookController::class, 'forceDelete'])->name('books.force-delete');
    Route::resource('books', BookController::class)->except(['show']);

    // 3. Bulk Import & Calibre Migration
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('imports/scan', [ImportController::class, 'scan'])->name('imports.scan');
    Route::post('imports/csv', [ImportController::class, 'importCsv'])->name('imports.csv');
    Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('imports/{import}', [ImportController::class, 'show'])->name('imports.show');

    // 4. Authors & Publishers
    Route::post('authors/{author}/merge', [AuthorController::class, 'merge'])->name('authors.merge');
    Route::resource('authors', AuthorController::class)->except(['show']);

    // 5. Disciplines & Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // 6. Executive Insights
    Route::resource('insights', InsightController::class)->except(['show']);

    // Hero Slides Management
    Route::post('hero-slides/{heroSlide}/toggle', [HeroSlideController::class, 'toggleActive'])->name('hero-slides.toggle');
    Route::resource('hero-slides', HeroSlideController::class)->except(['show']);

    // 7. Curated Collections
    Route::resource('collections', CollectionController::class)->except(['show']);

    // 8. Readers & Membership
    Route::get('readers', [ReaderAdminController::class, 'index'])->name('readers.index');
    Route::get('readers/{reader}', [ReaderAdminController::class, 'show'])->name('readers.show');
    Route::post('readers/{reader}/toggle-status', [ReaderAdminController::class, 'toggleStatus'])->name('readers.toggle-status');
    Route::post('readers/{reader}/verify-email', [ReaderAdminController::class, 'verifyEmail'])->name('readers.verify-email');

    // 9. Administrators & Roles
    Route::resource('administrators', AdministratorController::class)->except(['show']);

    // 10. Reports & Analytics
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export-books', [ReportController::class, 'exportBooksCsv'])->name('reports.export-books');

    // 11. Activity & Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // 12. Portal Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    // 13. System & Backups
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'createBackup'])->name('backups.create');
});
