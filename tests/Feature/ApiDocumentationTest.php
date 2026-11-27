<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ApiDocumentationTest extends TestCase
{
    public function createApplication()
    {
        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'local';
        try {
            return parent::createApplication();
        } finally {
            putenv('APP_ENV=testing');
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        }
    }

    public function test_local_documentation_serves_the_contract_and_local_assets(): void
    {
        $this->get('http://admin.web.test/dev/api-docs')->assertOk()->assertSee('SwaggerUIBundle', false);
        $response = $this->getJson('http://admin.web.test/dev/openapi.json')->assertOk();
        $definition = $response->json();
        self::assertSame('http://web.test', $definition['servers'][0]['url']);
        self::assertSame('/', $definition['paths']['/api/v1/auth/login']['servers'][0]['url']);
        self::assertSame('/', $definition['paths']['/api/v1/admin/projects']['servers'][0]['url']);
        self::assertSame(['http://web.test', '/'], array_column($definition['paths']['/api/v1/public/settings']['servers'], 'url'));
        self::assertStringNotContainsString('https://admin.3earstudio.com', $response->getContent());
        self::assertStringNotContainsString('https://3earstudio.com', $response->getContent());
        $this->get('http://admin.web.test/dev/swagger/swagger-ui.css')->assertOk();
        $this->get('http://web.test/dev/openapi.json')->assertNotFound();
        $this->app->instance('env', 'production');
        foreach (['/dev/api-docs', '/dev/openapi.json', '/dev/swagger/swagger-ui.css'] as $path) {
            $this->get('http://admin.web.test'.$path)->assertNotFound();
        }
    }

    public function test_local_contract_preserves_the_public_port(): void
    {
        config(['app.url' => 'http://web.test:8100']);

        $this->getJson('http://admin.web.test:8100/dev/openapi.json')->assertOk()->assertJsonPath('servers.0.url', 'http://web.test:8100');
    }

    public function test_local_contract_refuses_a_production_public_destination(): void
    {
        config(['app.url' => 'https://3earstudio.com']);

        $this->getJson('http://admin.web.test/dev/openapi.json')->assertServiceUnavailable();
    }
}
