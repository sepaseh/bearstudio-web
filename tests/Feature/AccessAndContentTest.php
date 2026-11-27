<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AccessAndContentTest extends TestCase
{
    public function test_read_only_operator_cannot_write_or_escalate_permissions(): void
    {
        $this->signIn(['content.read', 'roles.read']);
        $this->admin('GET', '/api/v1/admin/faqs')->assertOk();
        $this->admin('POST', '/api/v1/admin/faqs', ['question' => 'Question', 'answer' => 'Answer'])->assertForbidden();
        $this->admin('POST', '/api/v1/admin/roles', ['name' => 'Escalated', 'permissions' => ['roles.write']])->assertForbidden();
        self::assertSame(0, DB::table('faqs')->count());
        self::assertSame(0, DB::table('access_audit')->count());
    }

    public function test_manager_cannot_remove_own_access_and_transaction_rolls_back(): void
    {
        $id = $this->signIn();
        $role = DB::table('administrator_roles')->where('administrator_id', $id)->value('role_id');
        $original = DB::table('roles')->where('id', $role)->value('permissions');
        $this->admin('PATCH', '/api/v1/admin/roles/'.$role, ['permissions' => ['roles.read', 'roles.write']])
            ->assertStatus(409)->assertHeader('X-Error-Code', 'SELF_MODIFICATION_FORBIDDEN');
        self::assertSame($original, DB::table('roles')->where('id', $role)->value('permissions'));
        self::assertSame(0, DB::table('access_audit')->count());
        $this->admin('PATCH', '/api/v1/admin/users/'.$id, ['isActive' => false])->assertStatus(409);
        self::assertSame(1, (int) DB::table('administrators')->where('id', $id)->value('is_active'));
    }

    public function test_last_access_manager_is_preserved_when_actor_is_not_a_manager(): void
    {
        $manager = $this->administrator(null, '9234567890');
        $this->signIn(['users.read', 'users.write']);
        // Scope validation prevents managing a user with stronger grants.
        $this->admin('PATCH', '/api/v1/admin/users/'.$manager, ['isActive' => false])->assertForbidden();
        self::assertSame(1, (int) DB::table('administrators')->where('id', $manager)->value('is_active'));
    }

    public function test_access_writes_require_recent_authentication(): void
    {
        $this->signIn();
        DB::table('administrator_sessions')->update(['issued_at' => time() - 901]);
        $this->admin('POST', '/api/v1/admin/roles', ['name' => 'New role', 'permissions' => []])
            ->assertForbidden()->assertHeader('X-Error-Code', 'REAUTH_REQUIRED');
        self::assertFalse(DB::table('roles')->where('name', 'New role')->exists());
    }

    public function test_grant_changes_invalidate_existing_sessions(): void
    {
        $id = $this->signIn();
        Access::revoke([$id]);
        $this->admin('GET', '/api/v1/admin/users')->assertUnauthorized();
    }

    public function test_faq_crud_order_and_stale_sort_are_atomic(): void
    {
        $this->signIn();
        $one = $this->admin('POST', '/api/v1/admin/faqs', ['question' => 'First?', 'answer' => 'First answer'])->assertCreated()->json('id');
        $two = $this->admin('POST', '/api/v1/admin/faqs', ['question' => 'Second?', 'answer' => 'Second answer'])->assertCreated()->json('id');
        $this->admin('PATCH', '/api/v1/admin/faqs/sort', ['ids' => [$two, $one]])->assertNoContent();
        $this->getJson('http://web.test/api/v1/public/faqs')->assertOk()->assertJsonPath('0.id', $two);
        $auditCount = DB::table('access_audit')->count();
        $this->admin('PATCH', '/api/v1/admin/faqs/sort', ['ids' => [$one]])->assertStatus(400);
        self::assertSame([$two, $one], DB::table('faqs')->orderBy('sort_order')->pluck('id')->all());
        self::assertSame($auditCount, DB::table('access_audit')->count());
        $this->admin('PATCH', '/api/v1/admin/faqs/'.$one, ['answer' => 'Updated'])->assertOk()->assertJsonPath('answer', 'Updated');
        $this->admin('POST', '/api/v1/admin/faqs', ['question' => '', 'answer' => 'Invalid'])->assertStatus(400);
        $this->admin('POST', '/api/v1/admin/faqs', ['question' => 'No token?', 'answer' => 'Rejected'], false)->assertStatus(400);
        self::assertSame(2, DB::table('faqs')->count());
        $this->admin('DELETE', '/api/v1/admin/faqs/'.$one)->assertNoContent();
        $this->getJson('http://web.test/api/v1/public/faqs')->assertJsonCount(1);
    }
}
