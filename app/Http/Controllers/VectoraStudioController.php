<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Vectora\Pinecone\Core\Exception\ApiException;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Laravel\Facades\Pinecone;

class VectoraStudioController extends Controller
{
    public function index(): View
    {
        return view('vectora.studio', [
            'configured' => $this->pineconeConfigured(),
            'connections' => $this->connectionNames(),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        if (! $this->pineconeConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => 'Set PINECONE_API_KEY and PINECONE_HOST in .env, then run php artisan config:clear.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'connection' => ['nullable', 'string', Rule::in($this->connectionNames())],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $store = Pinecone::connection($request->query('connection'));
            $stats = $store->describeIndexStats();
            $namespaces = [];
            foreach ($stats->namespaces as $summary) {
                $namespaces[] = [
                    'name' => $summary->name === '' ? '(default)' : $summary->name,
                    'vector_count' => $summary->vectorCount,
                ];
            }

            return response()->json([
                'ok' => true,
                'dimension' => $stats->dimension,
                'total_vector_count' => $stats->totalVectorCount,
                'metric' => $stats->metric,
                'namespaces' => $namespaces,
                'fetched_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFromThrowable($e);
        }
    }

    public function upsert(Request $request): JsonResponse
    {
        if (! $this->pineconeConfigured()) {
            return response()->json(['ok' => false, 'message' => 'Pinecone is not configured.'], 503);
        }

        $validator = Validator::make($request->all(), [
            'connection' => ['nullable', 'string', Rule::in($this->connectionNames())],
            'id' => ['required', 'string', 'max:512'],
            'text' => ['nullable', 'string', 'max:8000'],
            'vector' => ['nullable', 'string', 'max:1000000'],
            'metadata' => ['nullable', 'array'],
            'namespace' => ['nullable', 'string', 'max:256'],
            'pinecone_default_namespace' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (empty($data['text']) && empty($data['vector'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Provide either `text` (for embeddings) or `vector` (JSON array of floats).',
            ], 422);
        }
        if (! empty($data['text']) && ! empty($data['vector'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Send only one of `text` or `vector`, not both.',
            ], 422);
        }

        try {
            $store = Pinecone::connection($data['connection'] ?? null);

            if (! empty($data['text'])) {
                $stats = $store->describeIndexStats();
                $dim = $stats->dimension;
                $values = Pinecone::embed($data['text']);
            } else {
                $decoded = json_decode((string) $data['vector'], true);
                if (! is_array($decoded)) {
                    return response()->json(['ok' => false, 'message' => '`vector` must be a JSON array of numbers.'], 422);
                }
                $values = array_map(static fn ($v): float => (float) $v, $decoded);
                $stats = $store->describeIndexStats();
                $dim = $stats->dimension;
            }

            if (count($values) !== $dim) {
                return response()->json([
                    'ok' => false,
                    'message' => sprintf(
                        'Vector length is %d but this index expects %d dimensions. Adjust PINECONE_EMBEDDING_DRIVER / dimensions or paste a full-length vector.',
                        count($values),
                        $dim
                    ),
                ], 422);
            }

            $namespace = $this->resolveNamespace($request);
            $record = new VectorRecord(
                id: $data['id'],
                values: $values,
                metadata: ($data['metadata'] ?? []) === [] ? null : $data['metadata'],
            );
            $result = $store->upsert(new UpsertVectorsRequest([$record], $namespace));

            return response()->json([
                'ok' => true,
                'upserted_count' => $result->upsertedCount,
                'id' => $data['id'],
                'dimension' => $dim,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFromThrowable($e);
        }
    }

    public function query(Request $request): JsonResponse
    {
        if (! $this->pineconeConfigured()) {
            return response()->json(['ok' => false, 'message' => 'Pinecone is not configured.'], 503);
        }

        $validator = Validator::make($request->all(), [
            'connection' => ['nullable', 'string', Rule::in($this->connectionNames())],
            'text' => ['required', 'string', 'max:8000'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
            'namespace' => ['nullable', 'string', 'max:256'],
            'pinecone_default_namespace' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $topK = $data['top_k'] ?? 10;

        try {
            $store = Pinecone::connection($data['connection'] ?? null);
            $stats = $store->describeIndexStats();
            $dim = $stats->dimension;
            $vector = Pinecone::embed($data['text']);
            if (count($vector) !== $dim) {
                return response()->json([
                    'ok' => false,
                    'message' => sprintf(
                        'Embedding length %d does not match index dimension %d.',
                        count($vector),
                        $dim
                    ),
                ], 422);
            }

            $namespace = $this->resolveNamespace($request);
            $filter = $data['filter'] ?? null;
            if ($filter === []) {
                $filter = null;
            }

            $result = $store->query(new QueryVectorsRequest(
                vector: $vector,
                topK: $topK,
                namespace: $namespace,
                filter: $filter,
                includeMetadata: true,
                includeValues: false,
            ));

            $matches = $result->matches;
            usort($matches, static fn ($a, $b) => $b->score <=> $a->score);

            $rows = [];
            foreach ($matches as $m) {
                $rows[] = [
                    'id' => $m->id,
                    'score' => round($m->score, 6),
                    'metadata' => $m->metadata,
                ];
            }

            return response()->json([
                'ok' => true,
                'matches' => $rows,
                'top_k' => $topK,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFromThrowable($e);
        }
    }

    public function deleteVectors(Request $request): JsonResponse
    {
        if (! $this->pineconeConfigured()) {
            return response()->json(['ok' => false, 'message' => 'Pinecone is not configured.'], 503);
        }

        $validator = Validator::make($request->all(), [
            'connection' => ['nullable', 'string', Rule::in($this->connectionNames())],
            'ids' => ['required', 'string', 'max:8000'],
            'namespace' => ['nullable', 'string', 'max:256'],
            'pinecone_default_namespace' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $rawIds = preg_split('/[\s,]+/', $data['ids'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($rawIds === []) {
            return response()->json(['ok' => false, 'message' => 'Provide at least one vector id.'], 422);
        }

        try {
            $store = Pinecone::connection($data['connection'] ?? null);
            $namespace = $this->resolveNamespace($request);
            $store->delete(new DeleteVectorsRequest(
                namespace: $namespace,
                ids: array_values(array_unique($rawIds)),
            ));

            return response()->json([
                'ok' => true,
                'deleted_ids' => array_values(array_unique($rawIds)),
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFromThrowable($e);
        }
    }

    /**
     * @return list<string>
     */
    private function connectionNames(): array
    {
        $c = config('pinecone', []);
        /** @var array<string, mixed> $indexes */
        $indexes = $c['indexes'] ?? [];
        if ($indexes === [] && (($c['host'] ?? '') !== '' || ($c['namespace'] ?? '') !== '')) {
            return [(string) ($c['default'] ?? 'default')];
        }

        $names = [];
        foreach ($indexes as $name => $entry) {
            if (is_string($name) && is_array($entry)) {
                $names[] = $name;
            }
        }

        return $names !== [] ? $names : ['default'];
    }

    private function pineconeConfigured(): bool
    {
        $apiKey = (string) config('pinecone.api_key', '');
        if ($apiKey === '') {
            return false;
        }

        $c = config('pinecone', []);
        $defaultName = (string) ($c['default'] ?? 'default');
        $indexes = $c['indexes'] ?? [];
        $host = '';
        if (is_array($indexes) && isset($indexes[$defaultName]) && is_array($indexes[$defaultName])) {
            $host = (string) ($indexes[$defaultName]['host'] ?? '');
        }
        if ($host === '' && ($c['host'] ?? '') !== '') {
            $host = (string) $c['host'];
        }

        return $host !== '';
    }

    private function resolveNamespace(Request $request): ?string
    {
        if ($request->boolean('pinecone_default_namespace')) {
            return '';
        }
        if ($request->filled('namespace')) {
            return (string) $request->input('namespace');
        }

        return null;
    }

    private function jsonFromThrowable(\Throwable $e): JsonResponse
    {
        if ($e instanceof ApiException) {
            $status = $e->statusCode >= 400 && $e->statusCode < 600 ? $e->statusCode : 502;

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'pinecone_status' => $e->statusCode,
            ], $status);
        }

        report($e);

        $message = config('app.debug') ? $e->getMessage() : 'Something went wrong talking to Pinecone.';

        return response()->json(['ok' => false, 'message' => $message], 500);
    }
}
