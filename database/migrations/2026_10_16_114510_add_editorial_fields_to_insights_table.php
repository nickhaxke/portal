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
        Schema::table('insights', function (Blueprint $table) {
            $table->string('featured_image', 255)->nullable()->after('icon');
            $table->string('image_alt', 191)->nullable()->after('featured_image');
            $table->text('excerpt')->nullable()->after('image_alt');
            $table->string('author_name', 120)->nullable()->default('Executive Editorial Board')->after('excerpt');
            $table->unsignedInteger('sort_order')->default(0)->after('read_time');
            $table->boolean('is_featured')->default(false)->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insights', function (Blueprint $table) {
            $table->dropColumn([
                'featured_image',
                'image_alt',
                'excerpt',
                'author_name',
                'sort_order',
                'is_featured',
            ]);
        });
    }
};
