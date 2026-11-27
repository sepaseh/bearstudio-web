<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SYSTEM_ROLE = '50b051f2-c357-48a8-bec5-29a9c6bfc3ab';

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('normalized_name', 240)->unique();
            $table->text('permissions');
            $table->unsignedInteger('sort_order');
        });

        Schema::create('administrator_roles', function (Blueprint $table): void {
            $table->foreignUuid('administrator_id')->constrained('administrators')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->restrictOnDelete();
            $table->primary(['administrator_id', 'role_id']);
            $table->index('role_id');
        });

        Schema::create('access_audit', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id')->nullable();
            $table->string('action');
            $table->string('target');
            $table->text('metadata');
            $table->timestamp('created_at');
        });

        Schema::table('administrator_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('issued_at')->nullable();
        });

        $catalog = [
            'dashboard.read', 'content.read', 'content.write', 'articles.read', 'articles.write', 'media.read',
            'media.write', 'users.read', 'users.write', 'audit.read', 'roles.read', 'roles.write',
            'settings.read', 'settings.write',
        ];
        $this->insertRole(self::SYSTEM_ROLE, 'System administrator', $catalog, 0);

        $pilotGrants = ['content.read', 'content.write', 'media.read', 'media.write'];
        $administrators = DB::table('administrators')->orderBy('id')->get(['id', 'is_active', 'permissions']);
        $legacyRoles = [];
        foreach ($administrators as $user) {
            $permissions = array_values(array_unique(json_decode($user->permissions, true, flags: JSON_THROW_ON_ERROR)));
            sort($permissions, SORT_STRING);
            if ($permissions === []) {
                continue;
            }

            $key = json_encode($permissions, JSON_THROW_ON_ERROR);
            if ($administrators->count() === 1 && $user->is_active && $permissions === $pilotGrants) {
                $roleId = self::SYSTEM_ROLE;
            } else {
                if (! isset($legacyRoles[$key])) {
                    $position = count($legacyRoles) + 1;
                    $legacyRoles[$key] = (string) Str::uuid();
                    $this->insertRole($legacyRoles[$key], 'Legacy access '.$position, $permissions, $position);
                }
                $roleId = $legacyRoles[$key];
            }

            DB::table('administrator_roles')->insert(['administrator_id' => $user->id, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        Schema::table('administrator_sessions', function (Blueprint $table): void {
            $table->dropColumn('issued_at');
        });

        Schema::dropIfExists('access_audit');
        Schema::dropIfExists('administrator_roles');
        Schema::dropIfExists('roles');
    }

    private function insertRole(string $id, string $name, array $permissions, int $position): void
    {
        DB::table('roles')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => mb_strtoupper($name, 'UTF-8'),
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
            'sort_order' => $position,
        ]);
    }
};
