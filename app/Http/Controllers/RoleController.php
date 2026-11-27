<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Normalizer;

final class RoleController
{
    public function __construct(private readonly AdministratorSession $administrator) {}

    public function index(Request $request): JsonResponse
    {
        $this->administrator->require($request, 'roles.read');
        AccessInput::noQuery($request);

        return $this->json(DB::table('roles')->orderBy('sort_order')->orderBy('name')->orderBy('id')->get()
            ->map(Access::roleRecord(...))->all());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->administrator->require($request, 'roles.read');
        AccessInput::noQuery($request);

        return $this->json(Access::roleRecord(Access::role(strtolower($id))));
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->administrator->require($request, 'roles.read');
        AccessInput::noQuery($request);

        return $this->json(Access::catalog());
    }

    public function store(Request $request): JsonResponse
    {
        $role = WriteTransaction::run(function () use ($request): array {
            $actor = $this->administrator->requireAccessWrite($request, 'roles.write');
            $input = AccessInput::read($request, ['name', 'permissions']);
            $name = AccessInput::name($input->name ?? null);
            $permissions = $this->grants($input->permissions ?? null);
            Access::requireScope($actor['permissions'], $permissions);
            $normalizedName = $this->requireUniqueName($name);
            $id = (string) Str::uuid();
            $nextPosition = (int) (DB::table('roles')->max('sort_order') ?? -1) + 1;

            DB::table('roles')->insert([
                'id' => $id,
                'name' => $name,
                'normalized_name' => $normalizedName,
                'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                'sort_order' => $nextPosition,
            ]);
            Access::ensureManagers($actor);
            Access::audit($actor['id'], 'roles.create', 'roles/'.$id, ['permissionCount' => count($permissions)]);

            return Access::roleRecord(Access::role($id));
        });

        return $this->json($role, 201)->header('Location', '/api/v1/admin/roles/'.$role['id']);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $id = strtolower($id);
        $role = WriteTransaction::run(function () use ($request, $id): array {
            $actor = $this->administrator->requireAccessWrite($request, 'roles.write');
            $input = AccessInput::read($request, ['name', 'permissions']);
            if (! isset($input->name) && ! isset($input->permissions)) {
                AccessInput::invalid('input', 'Supply at least one change.');
            }
            $current = Access::roleRecord(Access::role($id));
            $name = isset($input->name) ? AccessInput::name($input->name) : $current['name'];
            $permissions = isset($input->permissions) ? $this->grants($input->permissions) : $current['permissions'];
            Access::requireScope($actor['permissions'], $current['permissions']);
            Access::requireScope($actor['permissions'], $permissions);

            $changes = [];
            $fields = [];
            if ($name !== $current['name']) {
                $changes['name'] = $name;
                $changes['normalized_name'] = $this->requireUniqueName($name, $id);
                $fields[] = 'name';
            }
            $memberIds = [];
            if (array_diff($permissions, $current['permissions']) || array_diff($current['permissions'], $permissions)) {
                $memberIds = DB::table('administrator_roles')->where('role_id', $id)->pluck('administrator_id')->all();
                foreach ($memberIds as $memberId) {
                    Access::requireScope($actor['permissions'], Access::permissions($memberId));
                }
                $changes['permissions'] = json_encode($permissions, JSON_THROW_ON_ERROR);
                $fields[] = 'permissions';
            }
            if ($changes !== []) {
                DB::table('roles')->where('id', $id)->update($changes);
            }
            Access::ensureManagers($actor);
            if ($memberIds !== []) {
                Access::revoke($memberIds);
            }
            Access::audit($actor['id'], 'roles.update', 'roles/'.$id, [
                'fields' => $fields,
                'memberCount' => count($memberIds),
            ]);

            return Access::roleRecord(Access::role($id));
        });

        return $this->json($role);
    }

    public function sort(Request $request): Response
    {
        WriteTransaction::run(function () use ($request): void {
            $actor = $this->administrator->requireAccessWrite($request, 'roles.write');
            $input = AccessInput::read($request, ['ids'], 256 * 1024);
            $ids = AccessInput::ids($input->ids ?? null, 'ids');
            $roles = DB::table('roles')->get()->keyBy('id');
            if (count($ids) !== $roles->count() || array_diff($ids, $roles->keys()->all()) !== []) {
                AccessInput::invalid('ids', 'Supply every current role ID exactly once.');
            }

            $changed = 0;
            foreach ($ids as $position => $id) {
                $role = $roles->get($id);
                Access::requireScope($actor['permissions'], Access::roleRecord($role)['permissions']);
                if ((int) $role->sort_order !== $position) {
                    DB::table('roles')->where('id', $id)->update(['sort_order' => $position]);
                    $changed++;
                }
            }
            Access::ensureManagers($actor);
            Access::audit($actor['id'], 'roles.sort', 'roles', [
                'itemCount' => count($ids),
                'changedCount' => $changed,
            ]);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function destroy(Request $request, string $id): Response
    {
        $id = strtolower($id);
        WriteTransaction::run(function () use ($request, $id): void {
            $actor = $this->administrator->requireAccessWrite($request, 'roles.write');
            AccessInput::noQuery($request);
            AccessInput::noBody($request);
            $role = Access::roleRecord(Access::role($id));
            Access::requireScope($actor['permissions'], $role['permissions']);
            if (DB::table('administrator_roles')->where('role_id', $id)->exists()) {
                ApiError::abort(409, 'ROLE_IN_USE', 'Remove all user assignments before deleting this role.');
            }

            DB::table('roles')->where('id', $id)->delete();
            Access::ensureManagers($actor);
            Access::audit($actor['id'], 'roles.delete', 'roles/'.$id);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    private function grants(mixed $value): array
    {
        $known = array_column(Access::catalog(), 'id');
        if (! is_array($value) || count($value) > count($known)) {
            AccessInput::invalid('permissions', 'Choose permissions from the catalog.');
        }

        $grants = [];
        foreach ($value as $permission) {
            if (! is_string($permission) || ! in_array($permission, $known, true) || isset($grants[$permission])) {
                AccessInput::invalid('permissions', 'Choose distinct permissions from the catalog.');
            }
            $grants[$permission] = true;
        }
        $permissions = array_keys($grants);
        sort($permissions, SORT_STRING);

        return $permissions;
    }

    private function requireUniqueName(string $name, ?string $except = null): string
    {
        $normalized = mb_strtoupper(Normalizer::normalize($name) ?: $name, 'UTF-8');
        $existing = DB::table('roles')->where('normalized_name', $normalized);
        if ($except !== null) {
            $existing->where('id', '!=', $except);
        }
        if ($existing->exists()) {
            AccessInput::invalid('name', 'A role with this name already exists.');
        }

        return $normalized;
    }

    private function json(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}
