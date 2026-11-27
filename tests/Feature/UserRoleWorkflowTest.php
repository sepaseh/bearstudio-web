<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Access;
use App\Support\ApiError;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UserRoleWorkflowTest extends TestCase
{
    public function test_users_roles_and_password_changes_preserve_grants_and_audit(): void
    {
        $this->signIn();
        $this->admin('GET', '/api/v1/admin/permissions')->assertOk();
        $id = $this->admin('POST', '/api/v1/admin/users', ['name' => 'Synthetic editor', 'mobile' => '9234567890', 'password' => 'EditorPassword123!'])->assertCreated()->json('id');
        $role = $this->admin('POST', '/api/v1/admin/roles', ['name' => 'Editors', 'permissions' => ['articles.read', 'articles.write']])->assertCreated()->json('id');
        $this->admin('PATCH', '/api/v1/admin/users/'.$id.'/roles', ['roleIds' => [$role]])->assertOk();
        $this->admin('DELETE', '/api/v1/admin/roles/'.$role)->assertStatus(409)->assertHeader('X-Error-Code', 'ROLE_IN_USE');
        $this->admin('GET', '/api/v1/admin/users/'.$id)->assertOk()->assertJsonPath('roles.0.id', $role);
        $stamp = DB::table('administrators')->where('id', $id)->value('security_stamp');
        $this->admin('PATCH', '/api/v1/admin/roles/'.$role, ['permissions' => ['articles.read']])->assertOk();
        self::assertNotSame($stamp, DB::table('administrators')->where('id', $id)->value('security_stamp'));
        $this->admin('PATCH', '/api/v1/admin/users/'.$id, ['name' => 'Renamed editor', 'isActive' => false])->assertOk()->assertJsonPath('isActive', false);
        $this->admin('POST', '/api/v1/admin/users/'.$id.'/password', ['newPassword' => 'NewEditorPassword123!'])->assertNoContent();
        $this->admin('PATCH', '/api/v1/admin/users/'.$id.'/roles', ['roleIds' => []])->assertOk();
        $this->admin('DELETE', '/api/v1/admin/roles/'.$role)->assertNoContent();
        self::assertGreaterThan(0, DB::table('access_audit')->count());
        foreach (DB::table('access_audit')->pluck('metadata') as $metadata) {
            self::assertStringNotContainsString('Password123!', $metadata);
        }
    }

    public function test_last_manager_guard_rolls_back_a_non_manager_actor_transaction(): void
    {
        $manager = $this->administrator(null, '9234567890');
        $actor = $this->signIn(['content.write']);
        try {
            DB::transaction(function () use ($manager, $actor): void {
                DB::table('administrators')->where('id', $manager)->update(['is_active' => false]);
                Access::ensureManagers(['id' => $actor, 'permissions' => ['content.write']]);
            });
            self::fail('The final manager must be preserved.');
        } catch (ApiError $error) {
            self::assertSame('LAST_ACCESS_MANAGER', $error->errorCode);
        }
        self::assertSame(1, (int) DB::table('administrators')->where('id', $manager)->value('is_active'));
    }
}
