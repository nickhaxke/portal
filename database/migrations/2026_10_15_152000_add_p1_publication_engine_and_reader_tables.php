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
        // 1. Bibliographic and Publication Type fields on books table
        Schema::table('books', function (Blueprint $table) {
            $table->string('publication_type', 50)->default('book')->after('title')->index();
            $table->string('subtitle', 255)->nullable()->after('title');
            $table->string('doi', 100)->nullable()->after('isbn');
            $table->string('publisher', 191)->nullable()->after('author_name');
            $table->string('edition', 50)->nullable()->after('publisher');
            $table->unsignedSmallInteger('publication_year')->nullable()->after('edition');
            $table->text('citation_text')->nullable()->after('description');
        });

        // 2. Hierarchical Categories (Discipline -> Subcategories)
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('categories')
                ->nullOnDelete();
        });

        // 3. Multi-Author & Contributor Roles pivot table
        Schema::create('book_author', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
            $table->string('role', 50)->default('author'); // 'lead_author', 'co_author', 'editor', 'translator'
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['book_id', 'author_id', 'role']);
        });

        // 4. Reading Progress Tracking table
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedInteger('current_page')->default(1);
            $table->unsignedInteger('total_pages')->default(1);
            $table->decimal('percent_complete', 5, 2)->default(0.00);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('last_read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        // 5. Reading Bookmarks table
        Schema::create('book_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->string('title', 191)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'book_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_bookmarks');
        Schema::dropIfExists('reading_progress');
        Schema::dropIfExists('book_author');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['publication_type']);
            $table->dropColumn([
                'publication_type',
                'subtitle',
                'doi',
                'publisher',
                'edition',
                'publication_year',
                'citation_text',
            ]);
        });
    }
};
