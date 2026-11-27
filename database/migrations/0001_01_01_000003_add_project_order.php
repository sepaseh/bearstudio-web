<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0);
        });

        $slugs = DB::table('projects')->orderBy('created_at')->orderBy('slug')->pluck('slug');
        foreach ($slugs as $position => $slug) {
            DB::table('projects')->where('slug', $slug)->update(['sort_order' => $position]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
