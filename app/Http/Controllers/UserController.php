<?php

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorPassword;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

final class UserController
{
    public function __construct(private readonly AdministratorSession $administrator) {}

    public function index(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);
        AccessInput::noBody($request);
        $this->administrator->require($request, 'users.read');

        $users = DB::table('administrators')->orderByRaw('LOWER(name)')->orderBy('id')->get();

        return response()->json($users->map($this->record(...)), headers: ['Cache-Control' => 'no-store']);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        AccessInput::noQuery($request);
        AccessInput::noBody($request);
        $this->administrator->require($request, 'users.read');

        return response()->json($this->record($this->user($id)), headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request): JsonResponse
    {
        $input = AccessInput::read($request, ['name', 'mobile', 'password']);
        $mobile = is_string($input->mobile ?? null) ? AdministratorSession::canonicalMobile($input->mobile) : null;
        if ($mobile === null) {
            AccessInput::invalid('mobile', 'Use ten digits starting with 9, without 0 or a country prefix.');
        }
        $name = AccessInput::name($input->name ?? null);
        $password = $this->passwordValue($input->password ?? null, 'password');
        $user = WriteTransaction::run(function () use ($request, $name, $mobile, $password): array {
            $actor = $this->administrator->requireAccessWrite($request, 'users.write');
            if (DB::table('administrators')->where('mobile', $mobile)->exists()) {
                AccessInput::invalid('mobile', 'An account already uses this mobile number.');
            }

            $id = (string) Str::uuid();
            DB::table('administrators')->insert([
                'id' => $id,
                'mobile' => $mobile,
                'name' => $name,
                'password_hash' => AdministratorPassword::hash($password),
                'security_stamp' => (string) Str::uuid(),
                'permissions' => '[]',
            ]);
            Access::ensureManagers($actor);
            Access::audit($actor['id'], 'user.created', $id, ['roleCount' => 0]);

            return $this->record($this->user($id));
        });

        return response()->json($user, 201, ['Cache-Control' => 'no-store', 'Location' => '/api/v1/admin/users/'.$user['id']]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $input = AccessInput::read($request, ['name', 'isActive']);
        $id = strtolower($id);
        $changes = [];
        if (isset($input->name)) {
            $changes['name'] = AccessInput::name($input->name);
        }
        if (isset($input->isActive)) {
            if (! is_bool($input->isActive)) {
                AccessInput::invalid('isActive', 'Choose an active or inactive state.');
            }
            $changes['is_active'] = $input->isActive;
        }
        if ($changes === []) {
            AccessInput::invalid('input', 'Supply at least one change.');
        }

        $user = WriteTransaction::run(function () use ($request, $id, $changes): array {
            $actor = $this->administrator->requireAccessWrite($request, 'users.write');
            $target = $this->user($id);
            Access::requireScope($actor['permissions'], Access::permissions($id));
            DB::table('administrators')->where('id', $id)->update($changes);
            Access::ensureManagers($actor);
            if (($changes['is_active'] ?? (bool) $target->is_active) !== (bool) $target->is_active) {
                Access::revoke([$id]);
            }
            Access::audit($actor['id'], 'user.updated', $id, [
                'fields' => array_keys($changes),
                'rolesChanged' => false,
            ]);

            return $this->record($this->user($id));
        });

        return response()->json($user, headers: ['Cache-Control' => 'no-store']);
    }

    public function roles(Request $request, string $id): JsonResponse
    {
        $input = AccessInput::read($request, ['roleIds']);
        $roleIds = AccessInput::ids($input->roleIds ?? null, 'roleIds', 20);
        $id = strtolower($id);
        $user = WriteTransaction::run(function () use ($request, $id, $roleIds): array {
            $actor = $this->administrator->requireAccessWrite($request, 'users.write');
            $this->user($id);
            Access::requireScope($actor['permissions'], Access::permissions($id));
            Access::requireScope($actor['permissions'], $this->grants($roleIds));
            $currentRoles = array_column(Access::memberships($id), 'id');
            if (array_diff($currentRoles, $roleIds) !== [] || array_diff($roleIds, $currentRoles) !== []) {
                $this->assign($id, $roleIds);
                Access::ensureManagers($actor);
                Access::revoke([$id]);
                Access::audit($actor['id'], 'user.updated', $id, ['fields' => [], 'rolesChanged' => true]);
            }

            return $this->record($this->user($id));
        });

        return response()->json($user, headers: ['Cache-Control' => 'no-store']);
    }

    public function password(Request $request, string $id): Response
    {
        $input = AccessInput::read($request, ['newPassword']);
        $password = $this->passwordValue($input->newPassword ?? null, 'newPassword');
        $id = strtolower($id);

        WriteTransaction::run(function () use ($request, $id, $password): void {
            $actor = $this->administrator->requireAccessWrite($request, 'users.write');
            $this->user($id);
            Access::requireScope($actor['permissions'], Access::permissions($id));
            Access::ensureManagers($actor);
            DB::table('administrators')->where('id', $id)->update([
                'password_hash' => AdministratorPassword::hash($password),
                'locked_until' => null,
                'failed_attempts' => 0,
            ]);
            Access::revoke([$id]);
            Access::audit($actor['id'], 'user.password_changed', $id);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    private function user(string $id): stdClass
    {
        return DB::table('administrators')->where('id', strtolower($id))->first()
            ?? ApiError::abort(404, 'NOT_FOUND', 'User not found.');
    }

    private function record(stdClass $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'roles' => array_map(
                static fn ($role) => ['id' => $role->id, 'name' => $role->name],
                Access::memberships($user->id),
            ),
            'isActive' => (bool) $user->is_active,
        ];
    }

    private function grants(array $roleIds): array
    {
        $sets = DB::table('roles')->whereIn('id', $roleIds)->pluck('permissions')->all();
        if (count($sets) !== count($roleIds)) {
            AccessInput::invalid('roleIds', 'Choose only existing roles.');
        }

        return Access::effectivePermissions($sets);
    }

    private function assign(string $userId, array $roleIds): void
    {
        DB::table('administrator_roles')->where('administrator_id', $userId)->delete();
        if ($roleIds !== []) {
            DB::table('administrator_roles')->insert(array_map(
                static fn (string $roleId) => ['administrator_id' => $userId, 'role_id' => $roleId],
                $roleIds,
            ));
        }
    }

    private function passwordValue(mixed $value, string $field): string
    {
        if (! AdministratorPassword::valid($value)) {
            AccessInput::invalid($field, AdministratorPassword::POLICY);
        }

        return $value;
    }
}
