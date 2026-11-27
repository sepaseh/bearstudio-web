<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\SettingsInput;
use App\Support\SiteSettings;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SettingsController
{
    public function __construct(private readonly AdministratorSession $session) {}

    public function show(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);
        $this->session->require($request, 'settings.read');

        return $this->json(SiteSettings::adminRecord());
    }

    public function update(Request $request): JsonResponse
    {
        $record = WriteTransaction::run(function () use ($request): array {
            $actor = $this->session->require($request, 'settings.write', true);
            $input = AccessInput::read($request, SettingsInput::FIELDS);
            $changes = SettingsInput::changes($input, SiteSettings::profiles());
            DB::table('app_settings')->where('id', 1)->update($changes + ['updated_at' => now()]);
            Access::audit($actor['id'], 'settings.update', 'settings/1');

            return SiteSettings::adminRecord();
        });

        return $this->json($record);
    }

    public function published(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);

        return $this->json(SiteSettings::publicRecord());
    }

    private function json(array $record): JsonResponse
    {
        return response()->json($record, headers: ['Cache-Control' => 'no-store']);
    }
}
