<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->text('description')->default('');
            $table->text('tags')->default('[]');
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('service_images', function (Blueprint $table): void {
            $table->string('service_slug', 160);
            $table->foreignUuid('image_id')->constrained('images')->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('alt', 300)->default('');
            $table->primary(['service_slug', 'sort_order']);
            $table->unique(['service_slug', 'image_id']);
            $table->index('image_id');
            $table->foreign('service_slug')->references('slug')->on('services')->cascadeOnUpdate()->cascadeOnDelete();
        });

        // The trial seeded this option only so its project editor could be exercised.
        DB::table('services')->where('slug', 'pilot')->where('title', 'Pilot')
            ->whereNotExists(DB::table('projects')->selectRaw('1')->where('service_slug', 'pilot'))
            ->delete();

        $slugs = DB::table('services')->orderBy('title')->orderBy('slug')->pluck('slug');
        foreach ($slugs as $position => $slug) {
            DB::table('services')->where('slug', $slug)->update(['sort_order' => $position]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['description', 'tags', 'sort_order']);
        });

        DB::table('services')->insertOrIgnore(['slug' => 'pilot', 'title' => 'Pilot']);
    }
};
