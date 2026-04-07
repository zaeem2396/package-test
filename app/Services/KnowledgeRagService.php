<?php

namespace App\Services;

use App\Dto\RagAnswerPayload;
use Illuminate\Support\Facades\Log;
use Vectora\Pinecone\DTO\RagSourceChunk;
use Vectora\Pinecone\Laravel\Embeddings\LLMManager;
use Vectora\Pinecone\Rag\RagPromptBuilder;

/**
 * RAG: retrieve chunks → build prompt → LLM, with timing + structured logging.
 */
final class KnowledgeRagService
{
    public function __construct(
        private readonly KnowledgeRetrievalService $retrieval,
        private readonly LLMManager $llmManager,
        private readonly RagPromptBuilder $promptBuilder = new RagPromptBuilder,
    ) {}

    public function answer(
        string $question,
        string $modelClass,
        int $topK,
        bool $withSources,
        ?string $llmDriver = null,
    ): RagAnswerPayload {
        $t0 = hrtime(true);

        $chunkResult = $this->retrieval->retrieveForRag($modelClass, $question, $topK, null);

        $system = (string) config(
            'knowledge.system_prompt',
            'Answer using only the provided context when possible. If context is insufficient, say so briefly. Cite ideas, not file names.'
        );

        $messages = $this->promptBuilder->buildMessages(
            $chunkResult->chunks,
            $question,
            $system,
            [],
        );

        $tLlm = hrtime(true);
        $answer = $this->llmManager->driver($llmDriver)->chat($messages);
        $llmMs = (hrtime(true) - $tLlm) / 1e6;
        $totalMs = (hrtime(true) - $t0) / 1e6;

        $meta = [
            'embedding_ms' => round($chunkResult->embedMs, 2),
            'query_ms' => round($chunkResult->queryMs, 2),
            'llm_ms' => round($llmMs, 2),
            'total_ms' => round($totalMs, 2),
        ];

        $payload = new RagAnswerPayload(
            answer: $answer,
            sources: $withSources ? $this->formatSources($chunkResult->chunks) : [],
            meta: $meta,
        );

        Log::info('knowledge.rag.completed', [
            'model_class' => $modelClass,
            'top_k' => $topK,
            'chunks_used' => count($chunkResult->chunks),
            'meta' => $meta,
        ]);

        return $payload;
    }

    /**
     * @param  list<RagSourceChunk>  $chunks
     * @return list<array{id: string, score: float, model_id?: int|null, chunk_index?: int|null}>
     */
    private function formatSources(array $chunks): array
    {
        $out = [];
        foreach ($chunks as $c) {
            $meta = $c->metadata;
            $row = [
                'id' => $c->id,
                'score' => round($c->score, 4),
            ];
            if (isset($meta['model_id'])) {
                $row['model_id'] = is_numeric($meta['model_id']) ? (int) $meta['model_id'] : null;
            }
            if (isset($meta['chunk_index']) && is_numeric($meta['chunk_index'])) {
                $row['chunk_index'] = (int) $meta['chunk_index'];
            }
            $out[] = $row;
        }

        return $out;
    }
}
