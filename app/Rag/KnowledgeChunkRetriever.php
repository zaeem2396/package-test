<?php

namespace App\Rag;

use App\Services\KnowledgeRetrievalService;
use Vectora\Pinecone\Contracts\RagRetrieverContract;
use Vectora\Pinecone\Rag\RagPipeline;

/**
 * {@see RagRetrieverContract} adapter for chunked knowledge metadata (use with Vectora {@see RagPipeline}).
 */
final class KnowledgeChunkRetriever implements RagRetrieverContract
{
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function retrieve(string $query, int $topK = 5, ?array $additionalFilter = null): array
    {
        return app(KnowledgeRetrievalService::class)->retrieveForRag(
            $this->modelClass,
            $query,
            $topK,
            $additionalFilter
        )->chunks;
    }
}
