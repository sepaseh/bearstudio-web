<?php

use App\Http\Controllers\ArticleCategoryController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebsiteDiscoveryController;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$adminHost = config('pilot.admin_host');
$publicHost = parse_url(config('app.url'), PHP_URL_HOST);
$withoutSession = [StartSession::class, ShareErrorsFromSession::class];

Route::domain($adminHost)->group(function () use ($withoutSession): void {
    Route::prefix('api/v1')->group(function (): void {
        Route::controller(AuthController::class)->prefix('auth')->group(function (): void {
            Route::get('csrf', 'csrf');
            Route::post('login', 'login');
            Route::get('session', 'session');
            Route::post('logout', 'logout');
        });

        Route::prefix('admin')->group(function (): void {
            Route::controller(ProjectController::class)->group(function (): void {
                Route::get('projects', 'index');
                Route::post('projects', 'store');
                Route::put('projects/sort', 'sort');
                Route::get('projects/{slug}', 'show');
                Route::patch('projects/{slug}', 'update');
                Route::delete('projects/{slug}', 'destroy');
            });

            Route::controller(MediaController::class)->group(function (): void {
                Route::get('images', 'listImages');
                Route::post('images', 'uploadImage');
                Route::post('images/cleanup', 'cleanupImages');
                Route::get('images/{id}', 'getImage')->whereUuid('id');
                Route::delete('images/{id}', 'deleteImage')->whereUuid('id');
                Route::get('videos', 'listVideos');
                Route::post('videos', 'uploadVideo');
                Route::post('videos/cleanup', 'cleanupVideos');
                Route::get('videos/{id}', 'getVideo')->whereUuid('id');
                Route::delete('videos/{id}', 'deleteVideo')->whereUuid('id');
                Route::patch('videos/{id}', 'updateVideoPoster')->whereUuid('id');
            });

            Route::controller(ServiceController::class)->prefix('services')->group(function (): void {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('sort', 'sort');
                Route::get('{slug}', 'show');
                Route::patch('{slug}', 'update');
                Route::delete('{slug}', 'destroy');
            });

            Route::controller(FaqController::class)->prefix('faqs')->group(function (): void {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('sort', 'sort');
                Route::get('{id}', 'show')->whereUuid('id');
                Route::patch('{id}', 'update')->whereUuid('id');
                Route::delete('{id}', 'destroy')->whereUuid('id');
            });

            Route::get('pages/home', [HomeController::class, 'show']);
            Route::put('pages/home', [HomeController::class, 'update']);
            Route::get('settings', [SettingsController::class, 'show']);
            Route::put('settings', [SettingsController::class, 'update']);

            Route::controller(UserController::class)->group(function (): void {
                Route::get('users', 'index');
                Route::post('users', 'store');
                Route::get('users/{id}', 'show')->whereUuid('id');
                Route::patch('users/{id}', 'update')->whereUuid('id');
                Route::patch('users/{id}/roles', 'roles')->whereUuid('id');
                Route::post('users/{id}/password', 'password')->whereUuid('id');
            });

            Route::controller(RoleController::class)->group(function (): void {
                Route::get('permissions', 'permissions');
                Route::get('roles', 'index');
                Route::post('roles', 'store');
                Route::patch('roles/sort', 'sort');
                Route::get('roles/{id}', 'show')->whereUuid('id');
                Route::patch('roles/{id}', 'update')->whereUuid('id');
                Route::delete('roles/{id}', 'destroy')->whereUuid('id');
            });

            Route::controller(ArticleController::class)->group(function (): void {
                Route::get('articles', 'index');
                Route::post('articles', 'store');
                Route::get('articles/{id}', 'show')->whereUuid('id');
                Route::patch('articles/{id}', 'update')->whereUuid('id');
                Route::delete('articles/{id}', 'destroy')->whereUuid('id');
            });

            Route::controller(ArticleCategoryController::class)->group(function (): void {
                Route::get('article-categories', 'index');
                Route::post('article-categories', 'store');
                Route::patch('article-categories/sort', 'sort');
                Route::get('article-categories/{slug}', 'show');
                Route::patch('article-categories/{slug}', 'update');
                Route::delete('article-categories/{slug}', 'destroy');
            });
        });
    });

    Route::withoutMiddleware($withoutSession)->get('robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, [
        'Content-Type' => 'text/plain; charset=utf-8',
        'Cache-Control' => 'no-store',
    ]));
});

foreach ([$publicHost, $adminHost] as $host) {
    Route::domain($host)->withoutMiddleware($withoutSession)->prefix('api/v1/public')->group(function (): void {
        Route::get('settings', [SettingsController::class, 'published']);
        Route::controller(MediaController::class)->group(function (): void {
            Route::get('images/{id}', 'image')->whereUuid('id');
            Route::get('images/{id}/thumbnail', 'imageThumbnail')->whereUuid('id');
            Route::get('videos/{id}', 'video')->whereUuid('id');
            Route::get('videos/{id}/thumbnail', 'videoThumbnail')->whereUuid('id');
        });
    });
}

Route::domain($publicHost)->withoutMiddleware($withoutSession)->group(function (): void {
    Route::get('api/v1/public/projects', [ProjectController::class, 'publicIndex']);
    Route::get('api/v1/public/projects/{slug}', [ProjectController::class, 'publicShow']);
    Route::get('api/v1/public/services', [ServiceController::class, 'publicIndex']);
    Route::get('api/v1/public/pages/home', [HomeController::class, 'published']);
    Route::get('api/v1/public/faqs', [FaqController::class, 'published']);
    Route::get('api/v1/public/articles', [ArticleController::class, 'publicIndex']);
    Route::get('api/v1/public/articles/{slug}', [ArticleController::class, 'publicShow']);
    Route::get('api/v1/public/article-categories', [ArticleCategoryController::class, 'publicIndex']);
    Route::get('robots.txt', [WebsiteDiscoveryController::class, 'robots']);
    Route::get('sitemap.xml', [WebsiteDiscoveryController::class, 'sitemap']);

    Route::middleware(SetCacheHeaders::using(['no_store']))->group(function (): void {
        Route::get('services', [ServiceController::class, 'page'])->name('services');
        Route::controller(PublicSiteController::class)->group(function (): void {
            Route::get('/', 'home')->name('home');
            Route::get('projects', 'projects')->name('projects.index');
            Route::get('projects/{slug}', 'project')->name('projects.show');
            Route::get('articles', 'articles')->name('articles.index');
            Route::get('articles/{slug}', 'article')->name('articles.show');
        });
    });
});

// Development documentation is never registered in production.
if (app()->environment('local')) {
    Route::domain($adminHost)->withoutMiddleware($withoutSession)->group(function () use ($adminHost): void {
        Route::get('/dev/api-docs', function () {
            abort_unless(app()->environment('local'), 404);

            return response()->view('dev.api-docs')->header('Cache-Control', 'no-store');
        });
        Route::get('/dev/openapi.json', function () use ($adminHost) {
            abort_unless(app()->environment('local'), 404);

            $public = parse_url(config('app.url'));
            $scheme = $public['scheme'] ?? '';
            $host = $public['host'] ?? '';
            $productionHosts = ['3earstudio.com', 'admin.3earstudio.com'];
            abort_unless(
                in_array($scheme, ['http', 'https'], true) && $host !== ''
                && ! in_array(strtolower(rtrim($host, '.')), $productionHosts, true)
                && ! in_array(strtolower(rtrim($adminHost, '.')), $productionHosts, true),
                503,
                'Configure development public and admin hosts for Swagger UI.',
            );

            $publicOrigin = $scheme.'://'.$host.(isset($public['port']) ? ':'.$public['port'] : '');
            $developmentServers = [
                'https://3earstudio.com' => $publicOrigin,
                'https://admin.3earstudio.com' => '/',
            ];
            $mapServers = static fn (array $servers): array => array_map(static function (array $server) use ($developmentServers): array {
                abort_unless(isset($developmentServers[$server['url']]), 503, 'The API contract contains an unconfigured server.');
                $server['url'] = $developmentServers[$server['url']];

                return $server;
            }, $servers);

            $definition = json_decode(file_get_contents(base_path('docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
            $definition['servers'] = $mapServers($definition['servers']);
            foreach ($definition['paths'] as &$path) {
                if (isset($path['servers'])) {
                    $path['servers'] = $mapServers($path['servers']);
                }
                foreach (['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'] as $method) {
                    if (isset($path[$method]['servers'])) {
                        $path[$method]['servers'] = $mapServers($path[$method]['servers']);
                    }
                }
            }
            unset($path);

            return response()->json($definition)->header('Cache-Control', 'no-store');
        });
        Route::get('/dev/swagger/{asset}', function (string $asset) {
            abort_unless(app()->environment('local'), 404);
            abort_unless(in_array($asset, ['swagger-ui.css', 'swagger-ui-bundle.js'], true), 404);

            return response()->file(base_path('node_modules/swagger-ui-dist/'.$asset), ['Content-Type' => str_ends_with($asset, '.css') ? 'text/css' : 'application/javascript']);
        });
    });
}
