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
        Schema::table('books', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->index()->after('isbn');
            $table->boolean('allow_download')->default(false)->after('status');
            $table->string('visibility', 20)->default('public')->after('allow_download');
            $table->timestamp('published_at')->nullable()->index()->after('visibility');
            $table->timestamp('archived_at')->nullable()->after('published_at');
        });

        // Initialize published_at for existing publications
        DB::table('books')->whereNull('published_at')->update(['published_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['published_at']);
            $table->dropColumn(['status', 'allow_download', 'visibility', 'published_at', 'archived_at']);
        });
    }
};
