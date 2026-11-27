<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrators', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('mobile', 10)->unique();
            $table->string('name', 120);
            $table->string('password_hash');
            $table->uuid('security_stamp');
            $table->boolean('is_active')->default(true);
            $table->text('permissions');
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->unsignedBigInteger('locked_until')->nullable();
        });

        Schema::create('administrator_sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignUuid('administrator_id')->constrained('administrators')->cascadeOnDelete();
            $table->uuid('security_stamp');
            $table->unsignedBigInteger('last_activity_at');
            $table->unsignedBigInteger('expires_at')->index();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('administrator_sessions');
        Schema::dropIfExists('administrators');
    }
};
