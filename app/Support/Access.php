<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

final class Access
{
    private const MANAGER_PERMISSIONS = ['users.read', 'users.write', 'roles.read', 'roles.write'];

    public static function catalog(): array
    {
        return [
            ['id' => 'dashboard.read', 'name' => 'View dashboard', 'group' => 'Dashboard'],
            ['id' => 'content.read', 'name' => 'View content', 'group' => 'Content'],
            ['id' => 'content.write', 'name' => 'Manage content', 'group' => 'Content'],
            ['id' => 'articles.read', 'name' => 'View articles', 'group' => 'Articles'],
            ['id' => 'articles.write', 'name' => 'Manage articles', 'group' => 'Articles'],
            ['id' => 'media.read', 'name' => 'View media', 'group' => 'Media'],
            ['id' => 'media.write', 'name' => 'Manage media', 'group' => 'Media'],
            ['id' => 'users.read', 'name' => 'View users', 'group' => 'Users'],
            ['id' => 'users.write', 'name' => 'Manage users', 'group' => 'Users'],
            ['id' => 'audit.read', 'name' => 'View audit history', 'group' => 'Audit'],
            ['id' => 'roles.read', 'name' => 'View roles and permissions', 'group' => 'Roles'],
            ['id' => 'roles.write', 'name' => 'Manage roles', 'group' => 'Roles'],
            ['id' => 'settings.read', 'name' => 'View application settings', 'group' => 'Settings'],
            ['id' => 'settings.write', 'name' => 'Manage application settings', 'group' => 'Settings'],
        ];
    }

    public static function memberships(string $userId): array
    {
        return DB::table('administrator_roles')->join('roles', 'roles.id', '=', 'administrator_roles.role_id')
            ->where('administrator_roles.administrator_id', $userId)
            ->orderBy('roles.sort_order')->orderBy('roles.id')
            ->get(['roles.id', 'roles.name', 'roles.permissions'])->all();
    }

    public static function permissions(string $userId): array
    {
        return self::effectivePermissions(array_column(self::memberships($userId), 'permissions'));
    }

    public static function effectivePermissions(array $permissionSets): array
    {
        $permissions = [];
        foreach ($permissionSets as $permissionSet) {
            foreach (json_decode($permissionSet, true, flags: JSON_THROW_ON_ERROR) as $permission) {
                $permissions[$permission] = true;
            }
        }

        $keys = array_keys($permissions);
        sort($keys, SORT_STRING);

        return $keys;
    }

    public static function role(string $id): stdClass
    {
        return DB::table('roles')->where('id', $id)->first()
            ?? ApiError::abort(404, 'NOT_FOUND', 'Role not found.');
    }

    public static function roleRecord(stdClass $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => self::effectivePermissions([$role->permissions]),
        ];
    }

    public static function requireScope(array $actorPerms, array $grants): void
    {
        if (array_diff($grants, $actorPerms) !== []) {
            ApiError::abort(403, 'PRIVILEGE_ESCALATION', 'You cannot manage access outside your current permissions.');
        }
    }

    public static function ensureManagers(array $actor): void
    {
        $active = DB::table('administrators')->where('id', $actor['id'])->where('is_active', true)->exists();
        if (! $active || array_diff($actor['permissions'], self::permissions($actor['id'])) !== []) {
            ApiError::abort(409, 'SELF_MODIFICATION_FORBIDDEN', 'You cannot remove your own access or deactivate your account.');
        }

        $activeMemberships = DB::table('administrator_roles')
            ->join('administrators', 'administrators.id', '=', 'administrator_roles.administrator_id')
            ->join('roles', 'roles.id', '=', 'administrator_roles.role_id')
            ->where('administrators.is_active', true)
            ->get(['administrator_roles.administrator_id', 'roles.permissions'])
            ->groupBy('administrator_id');
        foreach ($activeMemberships as $memberships) {
            if (array_diff(self::MANAGER_PERMISSIONS, self::effectivePermissions($memberships->pluck('permissions')->all())) === []) {
                return;
            }
        }

        ApiError::abort(409, 'LAST_ACCESS_MANAGER', 'At least one active access manager must remain.');
    }

    public static function revoke(array $userIds): void
    {
        DB::table('administrators')->whereIn('id', $userIds)->update(['security_stamp' => (string) Str::uuid()]);
    }

    public static function audit(?string $actorId, string $action, string $target, array $details = []): void
    {
        DB::table('access_audit')->insert([
            'id' => (string) Str::uuid(),
            'actor_id' => $actorId,
            'action' => $action,
            'target' => $target,
            'metadata' => json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
