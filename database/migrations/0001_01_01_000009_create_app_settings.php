<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->text('site_json');
            $table->text('light_theme_json');
            $table->text('dark_theme_json');
            $table->text('image_profiles_json');
            $table->foreignUuid('light_logo_id')->nullable()->constrained('images')->restrictOnDelete();
            $table->foreignUuid('dark_logo_id')->nullable()->constrained('images')->restrictOnDelete();
            $table->timestamp('updated_at');
        });

        DB::table('app_settings')->insert([
            'id' => 1,
            'site_json' => '{"brandName":"Bear Studio","description":"","contactEmail":null,"contactTitle":"","contactDescription":"","socialLinks":[],"contact":{"phone":null,"whatsAppNumber":null}}',
            'light_theme_json' => '{"colorBgBase":"#eeece6","colorBgContainer":"#e0dfd7","colorBorder":"#23251f","colorLink":"#23251f","colorPrimary":"#a97621","colorTextBase":"#23251f","colorTextDescription":"#62665b"}',
            'dark_theme_json' => '{"colorBgBase":"#191b18","colorBgContainer":"#242821","colorBorder":"#e8e8df","colorLink":"#e8e8df","colorPrimary":"#edbd60","colorTextBase":"#e8e8df","colorTextDescription":"#a3a79a"}',
            'image_profiles_json' => '{"article":{"width":1024,"height":1536,"size":2097152},"project":{"width":1024,"height":1536,"size":5242880},"service":{"width":512,"height":512,"size":5242880},"logo":{"width":512,"height":128,"size":2097152},"video":{"width":200,"height":200,"size":2097152},"thumbnail":{"width":200,"height":200},"content":{"width":1600,"height":900,"size":5242880}}',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
