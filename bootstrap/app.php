<?php

use App\Http\Middleware\TrackRequest;
use App\Support\ApiError;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(TrackRequest::class);
        // Admin writes validate the existing panel's CSRF header explicitly.
        $middleware->web(remove: [PreventRequestForgery::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportWhen(static fn (Throwable $error): bool => $error instanceof ApiError && $error->status < 500);

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/v1', 'api/v1/*') || $exception instanceof ApiError) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $errors = [
                400 => ['VALIDATION_FAILED', 'Check the supplied request.'],
                404 => ['NOT_FOUND', 'The requested resource could not be found.'],
                405 => ['METHOD_NOT_ALLOWED', 'The method is unavailable for this route.'],
                413 => ['REQUEST_TOO_LARGE', 'The request exceeds the permitted size.'],
                415 => ['UNSUPPORTED_MEDIA_TYPE', 'The request uses an unsupported media type.'],
                429 => ['RATE_LIMITED', 'Too many requests. Try again later.'],
            ];
            [$code, $message] = $errors[$status] ?? ['INTERNAL_ERROR', 'The request could not be completed.'];

            return (new ApiError($status, $code, $message))->render($request);
        });
    })
    ->create();
