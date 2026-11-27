<?php

declare(strict_types=1);

namespace Tests\Contract;

use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class OpenApiTest extends TestCase
{
    public function test_documented_operations_match_registered_methods_paths_and_hosts(): void
    {
        $definition = json_decode(file_get_contents(base_path('docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $hosts = [
            '3earstudio.com' => parse_url(config('app.url'), PHP_URL_HOST),
            'admin.3earstudio.com' => config('pilot.admin_host'),
        ];
        $documented = [];
        foreach ($definition['paths'] as $path => $item) {
            foreach (['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'] as $method) {
                if (! isset($item[$method])) {
                    continue;
                }
                foreach ($item[$method]['servers'] ?? $item['servers'] ?? $definition['servers'] as $server) {
                    $host = parse_url($server['url'], PHP_URL_HOST);
                    self::assertArrayHasKey($host ?? '', $hosts, "Unknown server for $method $path");
                    $documented[] = strtoupper($method).' '.$hosts[$host].$path;
                }
            }
        }

        $registered = [];
        foreach ($this->app['router']->getRoutes() as $route) {
            $path = '/'.$route->uri();
            if (! str_starts_with($path, '/api/v1/') && ! in_array($path, ['/robots.txt', '/sitemap.xml'], true)) {
                continue;
            }
            $host = $route->getDomain();
            // The panel's deny-all robots file is a hosting control, not a documented website operation.
            if ($path === '/robots.txt' && $host === $hosts['admin.3earstudio.com']) {
                continue;
            }
            foreach ($route->methods() as $method) {
                $operation = $method.' '.($host ?? '(any host)').$path;
                if ($method === 'HEAD' && in_array('GET', $route->methods(), true) && ! in_array($operation, $documented, true)) {
                    continue;
                }
                $registered[] = $operation;
            }
        }

        self::assertSame([], array_values(array_diff($documented, $registered)), 'Documented operations without a matching route.');
        self::assertSame([], array_values(array_diff($registered, $documented)), 'Registered operations missing from the contract.');
    }

    public static function managementReads(): array
    {
        return array_map(fn ($resource) => ['/api/v1/admin/'.$resource], [
            'projects', 'services', 'articles', 'article-categories', 'faqs', 'images', 'videos', 'settings', 'users', 'roles', 'permissions',
        ]);
    }

    #[DataProvider('managementReads')]
    public function test_authenticated_management_responses_match_the_contract(string $path): void
    {
        $this->signIn();
        $response = $this->admin('GET', $path)->assertOk();
        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        $validator->validate(new OperationAddress($path, 'get'), new Response(200, $response->headers->all(), $response->getContent()));
    }

    public static function publicReads(): array
    {
        return array_map(fn ($path) => [$path], [
            '/api/v1/public/projects', '/api/v1/public/services',
            '/api/v1/public/faqs', '/api/v1/public/articles', '/api/v1/public/article-categories', '/api/v1/public/settings',
        ]);
    }

    #[DataProvider('publicReads')]
    public function test_public_responses_match_the_published_contract(string $path): void
    {
        $response = $this->getJson('http://web.test'.$path)->assertOk();
        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        $validator->validate(new OperationAddress($path, 'get'), new Response($response->status(), $response->headers->all(), $response->getContent()));
        self::assertTrue(true); // Successful validation establishes the response contract.
    }

    public function test_authentication_errors_and_csrf_match_the_contract(): void
    {
        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        foreach (['/api/v1/auth/csrf' => 200, '/api/v1/auth/session' => 401] as $path => $status) {
            $response = $this->admin('GET', $path)->assertStatus($status);
            $validator->validate(new OperationAddress($path, 'get'), new Response($status, $response->headers->all(), $response->getContent()));
        }
    }

    public function test_faq_creation_and_rejected_writes_match_the_contract(): void
    {
        $this->signIn();
        $path = '/api/v1/admin/faqs';
        $payload = ['question' => 'Example question?', 'answer' => 'Example answer.'];
        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        $operation = new OperationAddress($path, 'post');

        $withoutCsrf = $this->admin('POST', $path, $payload, csrf: false)
            ->assertBadRequest()->assertHeader('X-Error-Code', 'CSRF_VALIDATION_FAILED');
        $invalid = $this->admin('POST', $path, ['question' => $payload['question']])
            ->assertBadRequest()->assertHeader('X-Error-Code', 'VALIDATION_FAILED');
        $this->assertDatabaseCount('faqs', 0);

        $created = $this->admin('POST', $path, $payload)->assertCreated()->assertHeader('Location');
        foreach ([$withoutCsrf, $invalid, $created] as $response) {
            $validator->validate($operation, new Response($response->status(), $response->headers->all(), $response->getContent()));
        }
    }

    public function test_forbidden_faq_write_matches_the_contract(): void
    {
        $this->signIn(['content.read']);
        $path = '/api/v1/admin/faqs';
        $response = $this->admin('POST', $path, ['question' => 'Example?', 'answer' => 'Example answer.'])
            ->assertForbidden()->assertHeader('X-Error-Code', 'FORBIDDEN');
        $this->assertDatabaseCount('faqs', 0);

        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        $validator->validate(new OperationAddress($path, 'post'), new Response(403, $response->headers->all(), $response->getContent()));
    }

    public function test_project_field_validation_matches_the_contract(): void
    {
        $this->signIn();
        $path = '/api/v1/admin/projects';
        $response = $this->admin('POST', $path, ['title' => 'Example project'])
            ->assertBadRequest()->assertJsonPath('cause.slug', 'Provide a valid value for this field.');
        $this->assertDatabaseCount('projects', 0);

        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        $validator->validate(new OperationAddress($path, 'post'), new Response(400, $response->headers->all(), $response->getContent()));
    }

    public function test_unconfigured_home_returns_the_documented_not_found_response(): void
    {
        $response = $this->getJson('http://web.test/api/v1/public/pages/home')->assertNotFound();
        $validator = (new ValidatorBuilder)->fromJsonFile(base_path('docs/openapi.json'))->getResponseValidator();
        $validator->validate(new OperationAddress('/api/v1/public/pages/home', 'get'), new Response(404, $response->headers->all(), $response->getContent()));
    }
}
