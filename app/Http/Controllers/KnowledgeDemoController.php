<?php

namespace App\Http\Controllers;

use App\Facades\Knowledge;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Vectora\Pinecone\Core\Exception\ApiException;

final class KnowledgeDemoController extends Controller
{
    public function index(): View
    {
        $postCount = rescue(
            fn (): int => (int) Post::query()->count(),
            0
        );

        return view('knowledge.demo', [
            'postCount' => $postCount,
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'top_k' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'with_sources' => ['sometimes', 'boolean'],
        ]);

        $topK = (int) ($validated['top_k'] ?? config('knowledge.default_top_k', 5));
        $withSources = (bool) ($validated['with_sources'] ?? true);

        try {
            $payload = Knowledge::ask($validated['question'])
                ->from(Post::class)
                ->topK($topK)
                ->withSources($withSources)
                ->answer();

            return response()->json($payload->toArray());
        } catch (ApiException $e) {
            return response()->json([
                'error' => 'vector_store',
                'message' => $e->getMessage(),
            ], 502);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'server',
                'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong. Check logs.',
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:4000'],
            'top_k' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $topK = (int) ($validated['top_k'] ?? config('knowledge.default_top_k', 5));

        try {
            $result = Post::semanticSearch($validated['query'], $topK);
            $chunkKey = config('knowledge.metadata.chunk_text_key', 'chunk_text');

            $matches = [];
            foreach ($result->matches as $m) {
                $meta = is_array($m->metadata) ? $m->metadata : [];
                $snippet = isset($meta[$chunkKey]) && is_string($meta[$chunkKey])
                    ? mb_substr($meta[$chunkKey], 0, 320)
                    : null;
                $matches[] = [
                    'id' => $m->id,
                    'score' => round($m->score, 4),
                    'model_id' => isset($meta['model_id']) && is_numeric($meta['model_id'])
                        ? (int) $meta['model_id']
                        : null,
                    'chunk_index' => isset($meta['chunk_index']) && is_numeric($meta['chunk_index'])
                        ? (int) $meta['chunk_index']
                        : null,
                    'snippet' => $snippet,
                ];
            }

            return response()->json([
                'matches' => $matches,
                'count' => count($matches),
            ]);
        } catch (ApiException $e) {
            return response()->json([
                'error' => 'vector_store',
                'message' => $e->getMessage(),
            ], 502);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'server',
                'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong. Check logs.',
            ], 500);
        }
    }
}
