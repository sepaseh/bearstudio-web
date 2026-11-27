<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

final class RequestTrackingTest extends TestCase
{
    public function test_success_and_api_error_responses_correlate_with_safe_request_logs(): void
    {
        $logger = Log::spy();
        $ok = $this->get('http://admin.web.test/api/v1/auth/csrf', ['X-Request-Id' => 'synthetic-untrusted-id'])->assertOk();
        $id = $ok->headers->get('X-Request-Id');
        self::assertTrue(Str::isUuid($id));

        $failed = $this->getJson('http://admin.web.test/api/v1/synthetic-private-segment?token=synthetic-secret')->assertNotFound();
        $failureId = $failed->headers->get('X-Request-Id');
        self::assertTrue(Str::isUuid($failureId));
        self::assertNotSame($id, $failureId);

        foreach ([[$id, 200, 'api/v1/auth/csrf'], [$failureId, 404, null]] as [$requestId, $status, $route]) {
            $logger->shouldHaveReceived('log')->with('info', 'http.request.completed', Mockery::on(
                fn (array $fields): bool => $fields['request_id'] === $requestId
                    && $fields['method'] === 'GET' && $fields['status'] === $status && $fields['route'] === $route
                    && $fields['duration_ms'] >= 0
                    && ! str_contains(json_encode($fields, JSON_THROW_ON_ERROR), 'synthetic-')
            ))->once();
        }
    }

    public function test_write_request_records_lock_attempt_wait_and_hold_time(): void
    {
        $this->administrator();
        $this->token();
        $logger = Log::spy();
        $response = $this->admin('POST', '/api/v1/auth/login', [
            'mobile' => '9123456789', 'password' => 'SyntheticPass123!',
        ])->assertNoContent();

        $logger->shouldHaveReceived('log')->with('info', 'http.request.completed', Mockery::on(
            fn (array $fields): bool => $fields['request_id'] === $response->headers->get('X-Request-Id')
                && $fields['route'] === 'api/v1/auth/login' && $fields['write_mutex_attempts'] === 1
                && $fields['write_mutex_wait_ms'] > 0 && $fields['write_mutex_hold_ms'] > 0
                && ! str_contains(json_encode($fields, JSON_THROW_ON_ERROR), 'SyntheticPass123!')
        ))->once();
    }
}
