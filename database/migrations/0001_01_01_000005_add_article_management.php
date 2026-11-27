<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table): void {
            $table->string('slug', 160)->primary();
            $table->string('name', 120);
            $table->unsignedInteger('sort_order');
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->uuid('id')->nullable()->unique();
            $table->string('category_slug', 160)->nullable()->index();
            $table->boolean('body_is_html')->default(false);
            $table->foreign('category_slug')->references('slug')->on('article_categories')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        $legacySlugs = DB::table('articles')->pluck('slug');
        if ($legacySlugs->isNotEmpty()) {
            DB::table('article_categories')->insert([
                'slug' => 'uncategorized',
                'name' => 'Uncategorized',
                'sort_order' => 0,
            ]);
            foreach ($legacySlugs as $slug) {
                DB::table('articles')->where('slug', $slug)->update([
                    'id' => (string) Str::uuid(),
                    'category_slug' => 'uncategorized',
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropForeign(['category_slug']);
            $table->dropIndex(['category_slug']);
            $table->dropUnique(['id']);
            $table->dropColumn(['id', 'category_slug', 'body_is_html']);
        });
        Schema::dropIfExists('article_categories');
    }
};
