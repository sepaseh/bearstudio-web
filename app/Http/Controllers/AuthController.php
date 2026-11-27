<?php

namespace App\Http\Controllers;

use App\Support\AdministratorSession;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use JsonException;

final class AuthController
{
    public function __construct(private readonly AdministratorSession $administrator) {}

    public function csrf(Request $request): JsonResponse
    {
        $this->administrator->clearInvalidSession($request);

        return response()->json(['requestToken' => $request->session()->token()], headers: ['Cache-Control' => 'no-store']);
    }

    public function login(Request $request): Response
    {
        $key = 'bearstudio:login:'.hash('sha256', $request->ip() ?? '');
        if (RateLimiter::hit($key, decaySeconds: 60) > 5) {
            throw new ApiError(429, 'RATE_LIMITED', 'Too many sign-in attempts. Try again shortly.', headers: [
                'Retry-After' => (string) max(1, RateLimiter::availableIn($key)),
            ]);
        }

        if (! $request->isJson()) {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Use application/json.');
        }

        $body = $request->getContent();
        if (strlen($body) > 8 * 1024) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The sign-in request exceeds 8 KiB.');
        }

        $this->administrator->requireCsrf($request);
        try {
            $input = json_decode($body, false, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Use a valid JSON sign-in request.');
        }

        if (! is_object($input) || array_diff(array_keys(get_object_vars($input)), ['mobile', 'password']) !== []) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Check the supplied credentials.');
        }

        $mobile = is_string($input->mobile ?? null) ? AdministratorSession::canonicalMobile($input->mobile) : null;
        $password = $input->password ?? null;
        $errors = [];
        if ($mobile === null) {
            $errors['mobile'] = 'Use ten digits starting with 9, without 0 or a country prefix.';
        }
        if (! is_string($password) || $password === '' || mb_strlen($password, 'UTF-8') > 128) {
            $errors['password'] = 'A password of at most 128 characters is required.';
        }
        if ($errors !== []) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Check the supplied credentials.', $errors);
        }

        if (! $this->administrator->signIn($request, $mobile, $password)) {
            ApiError::abort(401, 'INVALID_CREDENTIALS', 'The mobile number or password is incorrect.');
        }

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function logout(Request $request): Response
    {
        $this->administrator->signOut($request);

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function session(Request $request): JsonResponse
    {
        try {
            $administrator = $this->administrator->require($request);
        } catch (ApiError $error) {
            return $error->render($request);
        }

        return response()->json($administrator, headers: ['Cache-Control' => 'no-store']);
    }
}
