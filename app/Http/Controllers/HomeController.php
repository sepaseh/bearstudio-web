<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\HomePage;
use App\Support\HomePageInput;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class HomeController
{
    public function __construct(private readonly AdministratorSession $session) {}

    public function show(Request $request): JsonResponse
    {
        $this->session->require($request, 'content.read');
        AccessInput::noQuery($request);
        $record = HomePage::record(public: false);
        if ($record === null) {
            return $this->missingHome($request);
        }

        return response()->json($record, headers: ['Cache-Control' => 'no-store']);
    }

    public function update(Request $request): JsonResponse
    {
        $record = WriteTransaction::run(function () use ($request): array {
            $actor = $this->session->require($request, 'content.write', true);
            $previous = DB::table('home_pages')->where('id', HomePage::ID)->first();
            $previousApproach = DB::table('home_page_approaches')->where('home_page_id', HomePage::ID)
                ->pluck('image_id', 'position')->all();
            [$values, $cards] = HomePageInput::read($request, $previous, $previousApproach);

            $now = now();
            DB::table('home_pages')->upsert(
                [$values + ['id' => HomePage::ID, 'updated_at' => $now, 'created_at' => $now]],
                ['id'],
                [...array_keys($values), 'updated_at'],
            );
            DB::table('home_page_approaches')->where('home_page_id', HomePage::ID)->delete();
            DB::table('home_page_approaches')->insert($cards);
            Access::audit($actor['id'], 'pages.home.save', 'pages/home', ['cardCount' => count($cards)]);

            return HomePage::record(public: false);
        });

        return response()->json($record, headers: ['Cache-Control' => 'no-store']);
    }

    public function published(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);
        $record = HomePage::record(public: true);
        if ($record === null) {
            return $this->missingHome($request);
        }

        return response()->json($record);
    }

    private function missingHome(Request $request): JsonResponse
    {
        return (new ApiError(404, 'NOT_FOUND', 'Home content not found.'))->render($request);
    }
}
