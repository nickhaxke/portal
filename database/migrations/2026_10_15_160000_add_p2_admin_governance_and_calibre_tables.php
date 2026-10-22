<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add soft deletes to books table
        Schema::table('books', function (Blueprint $table) {
            $table->softDeletes()->after('archived_at');
        });

        // 2. Add file_hash (SHA-256) and version to book_files
        Schema::table('book_files', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('file_size')->index();
            $table->unsignedSmallInteger('version')->default(1)->after('file_hash');
        });

        // 3. Multi-classification pivot table (book_category)
        Schema::create('book_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'category_id']);
        });

        // 4. Curated Collections
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->string('cover_image', 191)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. Collection to Book Pivot
        Schema::create('collection_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'book_id']);
        });

        // 6. Mutation Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // created, updated, published, archived, soft_deleted, restored, force_deleted
            $table->string('auditable_type', 100);
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
        });

        // 7. Bulk Import & Calibre Batches
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 50)->default('calibre'); // calibre, csv, json
            $table->string('source_path', 255)->nullable();
            $table->unsignedInteger('total_detected')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->json('summary_report')->nullable();
            $table->timestamps();
        });

        // 8. Portal Settings table
        Schema::create('portal_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_settings');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('collection_book');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('book_category');

        Schema::table('book_files', function (Blueprint $table) {
            $table->dropIndex(['file_hash']);
            $table->dropColumn(['file_hash', 'version']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
