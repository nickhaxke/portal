<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create libraries table (Calibre-style distinct libraries)
        Schema::create('libraries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('library');
            $table->string('cover_image', 191)->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->string('visibility', 20)->default('public')->index(); // public, restricted, private
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default library for existing catalog records
        $defaultLibraryId = DB::table('libraries')->insertGetId([
            'name' => 'Executive Core Library',
            'slug' => 'executive-core-library',
            'description' => 'Flagship repository of curated publications on strategic leadership, governance, and institutional wisdom.',
            'icon' => 'library',
            'is_published' => true,
            'visibility' => 'public',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Add library_id and professional metadata fields to books table
        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('library_id')
                ->nullable()
                ->after('id')
                ->constrained('libraries')
                ->nullOnDelete();

            $table->string('language', 20)->default('en')->after('publisher');
            $table->string('series', 191)->nullable()->after('edition');
            $table->decimal('series_number', 8, 2)->nullable()->after('series');
            $table->string('isbn13', 30)->nullable()->after('isbn');
        });

        // Assign existing books to the default library
        DB::table('books')->whereNull('library_id')->update(['library_id' => $defaultLibraryId]);

        // 3. Create professional tags system
        Schema::create('tags', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });

        Schema::create('book_tag', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['book_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_tag');
        Schema::dropIfExists('tags');

        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn(['library_id', 'language', 'series', 'series_number', 'isbn13']);
        });

        Schema::dropIfExists('libraries');
    }
};
