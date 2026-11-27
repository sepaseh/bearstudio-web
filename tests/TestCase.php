<?php

declare(strict_types=1);

namespace Tests;

use App\Support\Access;
use App\Support\AdministratorPassword;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\MariaDatabase;

abstract class TestCase extends LaravelTestCase
{
    protected string $sandbox;

    protected string $testDatabase;

    protected string $csrf = '';

    public function createApplication()
    {
        $this->traitsUsedByTest = class_uses_recursive(static::class);
        $this->sandbox = sys_get_temp_dir().'/bearstudio-test-'.bin2hex(random_bytes(10));
        foreach (['framework/views', 'framework/sessions', 'framework/cache', 'logs', 'db'] as $directory) {
            mkdir($this->sandbox.'/'.$directory, 0750, true);
        }
        $this->testDatabase = MariaDatabase::create();
        foreach (MariaDatabase::environment($this->testDatabase) as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
        $app = require dirname(__DIR__).'/bootstrap/app.php';
        $app->useEnvironmentPath($this->sandbox);
        $app->loadEnvironmentFrom('.env.testing');
        $app->useStoragePath($this->sandbox);
        $app->useBootstrapPath($this->sandbox.'/bootstrap');
        mkdir($this->sandbox.'/bootstrap/cache', 0750, true);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('view.compiled', $this->sandbox.'/framework/views');
        $app['config']->set('hashing.bcrypt.rounds', 4);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if (isset($this->testDatabase)) {
                MariaDatabase::drop($this->testDatabase);
            }
            if (isset($this->sandbox) && str_starts_with($this->sandbox, sys_get_temp_dir().'/bearstudio-test-')) {
                (new Filesystem)->deleteDirectory($this->sandbox);
            }
        }
    }

    protected function administrator(?array $permissions = null, string $mobile = '9123456789'): string
    {
        $id = (string) Str::uuid();
        $role = (string) Str::uuid();
        $permissions ??= array_column(Access::catalog(), 'id');
        DB::table('administrators')->insert([
            'id' => $id, 'name' => 'Synthetic operator', 'mobile' => $mobile,
            'password_hash' => AdministratorPassword::hash('SyntheticPass123!'),
            'security_stamp' => (string) Str::uuid(), 'is_active' => true, 'permissions' => '[]',
        ]);
        DB::table('roles')->insert([
            'id' => $role, 'name' => $role, 'normalized_name' => strtoupper($role),
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR), 'sort_order' => 100,
        ]);
        DB::table('administrator_roles')->insert(['administrator_id' => $id, 'role_id' => $role]);

        return $id;
    }

    protected function admin(string $method, string $path, array $data = [], bool $csrf = true): TestResponse
    {
        $headers = ['Accept' => 'application/json'];
        if ($csrf && $this->csrf !== '') {
            $headers['X-CSRF-TOKEN'] = $this->csrf;
        }
        $response = $data === []
            ? $this->call($method, 'http://admin.web.test'.$path, [], $this->prepareCookiesForRequest(), [], $this->transformHeadersToServerVars($headers), '')
            : $this->json($method, 'http://admin.web.test'.$path, $data, $headers);
        foreach ($response->headers->getCookies() as $cookie) {
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        }

        return $response;
    }

    protected function token(): string
    {
        $this->csrf = $this->admin('GET', '/api/v1/auth/csrf')->assertOk()->json('requestToken');

        return $this->csrf;
    }

    protected function signIn(?array $permissions = null): string
    {
        $id = $this->administrator($permissions);
        $this->token();
        $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'])->assertNoContent();
        $this->token();

        return $id;
    }
}
