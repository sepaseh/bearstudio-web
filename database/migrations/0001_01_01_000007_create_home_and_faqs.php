<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_pages', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->text('hero_description');
            $table->foreignUuid('hero_video_id')->nullable()->constrained('videos')->restrictOnDelete();
            $table->string('hero_video_url', 2048)->nullable();
            $table->foreignUuid('hero_poster_image_id')->constrained('images')->restrictOnDelete();
            $table->string('about_title', 200);
            $table->text('about_subtitle');
            $table->text('about_description');
            $table->foreignUuid('about_video_id')->nullable()->constrained('videos')->restrictOnDelete();
            $table->string('about_video_url', 2048)->nullable();
            $table->foreignUuid('about_poster_image_id')->nullable()->constrained('images')->restrictOnDelete();
            $table->string('about_poster_alt', 300)->default('');
            $table->string('why_title', 200);
            $table->text('why_subtitle');
            $table->text('why_description');
            $table->foreignUuid('why_image_id')->constrained('images')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('home_page_approaches', function (Blueprint $table): void {
            $table->unsignedInteger('home_page_id');
            $table->foreign('home_page_id')->references('id')->on('home_pages')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 200);
            $table->foreignUuid('image_id')->constrained('images')->restrictOnDelete();
            $table->primary(['home_page_id', 'position']);
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('question', 300);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('home_page_approaches');
        Schema::dropIfExists('home_pages');
    }
};
