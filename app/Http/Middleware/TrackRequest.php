<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class TrackRequest
{
    public const REQUEST_ID = 'bearstudio.request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $request->attributes->set(self::REQUEST_ID, $requestId);
        $started = hrtime(true);

        return Context::scope(function () use ($request, $requestId, $started, $next): Response {
            $response = $next($request);
            $response->headers->set('X-Request-Id', $requestId);

            $route = $request->route();
            $status = $response->getStatusCode();
            $fields = [
                'request_id' => $requestId,
                'method' => $request->getMethod(),
                'route' => $route instanceof Route ? $route->uri() : null,
                'status' => $status,
                'duration_ms' => round((hrtime(true) - $started) / 1e6, 2),
                ...Context::getHidden('write_mutex_timing', []),
            ];
            rescue(fn () => Log::log($status >= 500 ? 'warning' : 'info', 'http.request.completed', $fields), report: false);

            return $response;
        }, ['request_id' => $requestId]);
    }
}
