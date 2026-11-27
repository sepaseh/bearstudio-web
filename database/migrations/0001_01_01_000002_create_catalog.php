<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->string('slug', 160)->primary();
            $table->string('title', 200);
        });

        Schema::create('images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('context', 30);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size_bytes');
        });

        Schema::create('videos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('size_bytes');
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->string('slug', 160)->primary();
            $table->string('title', 500);
            $table->string('service_slug', 160);
            $table->text('summary')->default('');
            $table->uuid('poster_image_id')->nullable();
            $table->string('poster_url', 2048)->nullable();
            $table->uuid('full_video_id')->nullable();
            $table->string('full_video_url', 2048)->nullable();
            $table->uuid('preview_video_id')->nullable();
            $table->string('preview_video_url', 2048)->nullable();
            $table->string('watch_label', 80)->nullable();
            $table->string('watch_url', 2048)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->index('service_slug');
            $table->foreign('service_slug')->references('slug')->on('services')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('poster_image_id')->references('id')->on('images')->restrictOnDelete();
            $table->foreign('full_video_id')->references('id')->on('videos')->restrictOnDelete();
            $table->foreign('preview_video_id')->references('id')->on('videos')->restrictOnDelete();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->string('slug', 160)->primary();
            $table->string('title', 300);
            $table->text('excerpt');
            $table->text('body');
            $table->uuid('cover_image_id')->nullable();
            $table->timestamps();
            $table->foreign('cover_image_id')->references('id')->on('images')->restrictOnDelete();
        });

        DB::table('services')->insert(['slug' => 'pilot', 'title' => 'Pilot']);
    }

    public function down(): void
    {
        foreach (['articles', 'projects', 'videos', 'images', 'services'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
