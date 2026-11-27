<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\FaqCatalog;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

final class FaqController
{
    private const LIMIT = 1000;

    public function __construct(private readonly AdministratorSession $session) {}

    public function index(Request $request): JsonResponse
    {
        $this->session->require($request, 'content.read');
        AccessInput::noQuery($request);

        return response()->json(['items' => FaqCatalog::records()], headers: ['Cache-Control' => 'no-store']);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->session->require($request, 'content.read');
        AccessInput::noQuery($request);

        return response()->json($this->find(strtolower($id)), headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request): JsonResponse
    {
        $faq = WriteTransaction::run(function () use ($request): stdClass {
            $actor = $this->session->require($request, 'content.write', true);
            $input = AccessInput::read($request, ['question', 'answer']);
            $question = $this->text($input->question ?? null, 'question', 300);
            $answer = $this->text($input->answer ?? null, 'answer', 5000);
            if (DB::table('faqs')->count() >= self::LIMIT) {
                ApiError::abort(409, 'CONTENT_LIMIT', 'The FAQ catalog has reached its item limit.');
            }

            $id = (string) Str::uuid();
            $position = (DB::table('faqs')->max('sort_order') ?? -1) + 1;
            $now = now();
            DB::table('faqs')->insert([
                'id' => $id, 'question' => $question, 'answer' => $answer, 'sort_order' => $position,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            Access::audit($actor['id'], 'faqs.create', 'faqs/'.$id);

            return $this->find($id);
        });

        return response()->json($faq, 201, [
            'Cache-Control' => 'no-store', 'Location' => '/api/v1/admin/faqs/'.$faq->id,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $id = strtolower($id);
        $faq = WriteTransaction::run(function () use ($request, $id): stdClass {
            $actor = $this->session->require($request, 'content.write', true);
            $input = AccessInput::read($request, ['question', 'answer']);
            $changes = [];
            foreach (['question' => 300, 'answer' => 5000] as $field => $max) {
                if (isset($input->$field)) {
                    $changes[$field] = $this->text($input->$field, $field, $max);
                }
            }
            if ($changes === []) {
                AccessInput::invalid('input', 'Supply at least one editable field.');
            }
            $this->find($id);
            DB::table('faqs')->where('id', $id)->update($changes + ['updated_at' => now()]);
            Access::audit($actor['id'], 'faqs.update', 'faqs/'.$id);

            return $this->find($id);
        });

        return response()->json($faq, headers: ['Cache-Control' => 'no-store']);
    }

    public function destroy(Request $request, string $id): Response
    {
        $id = strtolower($id);
        WriteTransaction::run(function () use ($request, $id): void {
            $actor = $this->session->require($request, 'content.write', true);
            AccessInput::noQuery($request);
            AccessInput::noBody($request);
            if (DB::table('faqs')->where('id', $id)->delete() !== 1) {
                ApiError::abort(404, 'NOT_FOUND', 'FAQ not found.');
            }
            Access::audit($actor['id'], 'faqs.delete', 'faqs/'.$id);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function sort(Request $request): Response
    {
        WriteTransaction::run(function () use ($request): void {
            $actor = $this->session->require($request, 'content.write', true);
            $input = AccessInput::read($request, ['ids'], 65536);
            $ids = AccessInput::ids($input->ids ?? null, 'ids', self::LIMIT);
            $positions = DB::table('faqs')->pluck('sort_order', 'id')->all();
            if (count($ids) !== count($positions) || array_diff($ids, array_keys($positions)) !== []) {
                AccessInput::invalid('ids', 'Supply every current FAQ ID exactly once.');
            }

            $now = now();
            $changed = 0;
            foreach ($ids as $position => $id) {
                if ((int) $positions[$id] === $position) {
                    continue;
                }
                DB::table('faqs')->where('id', $id)->update(['sort_order' => $position, 'updated_at' => $now]);
                $changed++;
            }
            Access::audit($actor['id'], 'faqs.sort', 'faqs', ['itemCount' => count($ids), 'changedCount' => $changed]);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function published(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);

        return response()->json(FaqCatalog::records());
    }

    private function find(string $id): stdClass
    {
        return DB::table('faqs')->where('id', $id)->first(['id', 'question', 'answer'])
            ?? ApiError::abort(404, 'NOT_FOUND', 'FAQ not found.');
    }

    private function text(mixed $value, string $field, int $maximum): string
    {
        $text = is_string($value) && ! str_contains($value, "\0") ? preg_replace('/\A\s+|\s+\z/u', '', $value) : null;
        if ($text === null || $text === '' || mb_strlen($text, 'UTF-8') > $maximum) {
            AccessInput::invalid($field, 'Supply nonempty text of at most '.$maximum.' characters.');
        }

        return $text;
    }
}
