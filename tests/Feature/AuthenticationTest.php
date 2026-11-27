<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    public function test_login_rotates_the_session_and_logout_revokes_it(): void
    {
        $this->administrator();
        $this->token();
        $before = $this->app['session']->driver()->getId();
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '۹۱۲۳۴۵۶۷۸۹', 'password' => 'SyntheticPass123!'])->assertNoContent();
        self::assertNotSame($before, $this->app['session']->driver()->getId());
        $this->admin('GET', '/api/v1/auth/session')->assertOk()->assertJsonPath('mobile', '9123456789')->assertJsonMissingPath('password_hash');
        $this->token();
        $this->admin('POST', '/api/v1/auth/logout')->assertNoContent();
        $this->admin('GET', '/api/v1/auth/session')->assertUnauthorized();
        self::assertSame(0, DB::table('administrator_sessions')->count());
    }

    public function test_login_and_mutations_reject_missing_csrf(): void
    {
        $this->administrator();
        $this->token();
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'], false)
            ->assertStatus(400)->assertHeader('X-Error-Code', 'CSRF_VALIDATION_FAILED');
        self::assertSame(0, DB::table('administrator_sessions')->count());
    }

    public function test_wrong_password_locks_the_account_after_five_failures(): void
    {
        $id = $this->administrator();
        $this->token();
        for ($i = 0; $i < 5; $i++) {
            $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'wrong'])->assertUnauthorized();
        }
        self::assertGreaterThan(time(), DB::table('administrators')->where('id', $id)->value('locked_until'));
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'])->assertStatus(429)->assertHeader('Retry-After');
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'])->assertUnauthorized();
    }

    public function test_idle_absolute_expiry_and_security_stamp_revoke_sessions(): void
    {
        $id = $this->signIn();
        DB::table('administrator_sessions')->update(['last_activity_at' => time() - 901]);
        $this->admin('GET', '/api/v1/auth/session')->assertUnauthorized();
        $this->token();
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'])->assertNoContent();
        DB::table('administrator_sessions')->update(['expires_at' => time() - 1]);
        $this->admin('GET', '/api/v1/auth/session')->assertUnauthorized();
        $this->token();
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'])->assertNoContent();
        DB::table('administrators')->where('id', $id)->update(['security_stamp' => '2c8f70e4-b3ed-49d5-a535-2c208dfe5960']);
        $this->admin('GET', '/api/v1/auth/session')->assertUnauthorized();
    }

    public function test_session_cookie_is_host_only_http_only_and_lax(): void
    {
        $response = $this->admin('GET', '/api/v1/auth/csrf');
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        self::assertNotNull($cookie);
        self::assertNull($cookie->getDomain());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('lax', $cookie->getSameSite());
    }
}
