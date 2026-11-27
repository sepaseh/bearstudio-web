<?php

namespace App\Support;

use App\Http\Middleware\TrackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

final class ApiError extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $errors = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    /** @param array<string, string> $errors */
    public static function abort(int $status, string $code, string $message, array $errors = []): never
    {
        throw new self($status, $code, $message, $errors);
    }

    public function render(Request $request): JsonResponse
    {
        $requestId = $request->attributes->get(TrackRequest::REQUEST_ID) ?? (string) Str::uuid();
        $data = ['message' => $this->getMessage()];

        if ($this->errors !== []) {
            $data['cause'] = $this->errors;
        }

        return response()->json($data, $this->status, $this->headers + [
            'Cache-Control' => 'no-store',
            'X-Error-Code' => $this->errorCode,
            'X-Request-Id' => $requestId,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
