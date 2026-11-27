<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->index(['context', 'created_at', 'id']);
            $table->index(['created_at', 'id']);
        });

        Schema::table('videos', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->foreignUuid('poster_image_id')->nullable()->constrained('images')->restrictOnDelete();
            $table->index(['created_at', 'id']);
        });

        Schema::create('media_cleanup_intents', function (Blueprint $table): void {
            $table->string('kind', 8);
            $table->uuid('id');
            $table->string('reason', 8);
            $table->unsignedBigInteger('created_at');
            $table->unsignedBigInteger('last_attempt_at')->nullable();
            $table->primary(['kind', 'id']);
            $table->index(['kind', 'reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_cleanup_intents');

        Schema::table('videos', function (Blueprint $table): void {
            $table->dropForeign(['poster_image_id']);
            $table->dropIndex(['created_at', 'id']);
            $table->dropColumn(['created_at', 'poster_image_id']);
        });

        Schema::table('images', function (Blueprint $table): void {
            $table->dropIndex(['context', 'created_at', 'id']);
            $table->dropIndex(['created_at', 'id']);
            $table->dropColumn('created_at');
        });
    }
};
