<?php

namespace App\Services;

use App\Dto\KnowledgeChunkResult;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\RagSourceChunk;

/**
 * Pinecone retrieval for knowledge chunks (uses {@see VectorStoreContract} + {@see EmbeddingDriver}).
 */
final class KnowledgeRetrievalService
{
    public function __construct(
        private readonly EmbeddingDriver $embeddings,
        private readonly VectorStoreContract $vectorStore,
    ) {}

    /**
     * @param  array<string, mixed>|null  $additionalFilter
     */
    public function queryForModel(string $modelClass, string $query, int $topK, ?array $additionalFilter = null): QueryVectorsResult
    {
        return $this->runQuery($modelClass, $query, $topK, $additionalFilter)->raw;
    }

    /**
     * @param  array<string, mixed>|null  $additionalFilter
     */
    public function retrieveForRag(string $modelClass, string $query, int $topK, ?array $additionalFilter = null): KnowledgeChunkResult
    {
        return $this->runQuery($modelClass, $query, $topK, $additionalFilter);
    }

    /**
     * @param  array<string, mixed>|null  $additionalFilter
     */
    private function runQuery(string $modelClass, string $query, int $topK, ?array $additionalFilter): KnowledgeChunkResult
    {
        if ($topK < 1) {
            throw new \InvalidArgumentException('topK must be at least 1.');
        }

        $tEmbed = hrtime(true);
        $vector = $this->embeddings->embed($query);
        $embedMs = (hrtime(true) - $tEmbed) / 1e6;

        $filter = array_merge($this->baseFilter($modelClass), $additionalFilter ?? []);

        $tQuery = hrtime(true);
        $raw = $this->vectorStore->query(new QueryVectorsRequest(
            vector: $vector,
            topK: $topK,
            namespace: null,
            filter: $filter,
            includeMetadata: true,
            includeValues: false,
            queryByVectorId: null,
        ));
        $queryMs = (hrtime(true) - $tQuery) / 1e6;

        $chunks = [];
        foreach ($raw->matches as $match) {
            $chunks[] = $this->matchToSource($match);
        }

        return new KnowledgeChunkResult($chunks, $embedMs, $queryMs, $raw);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseFilter(string $modelClass): array
    {
        $m = config('knowledge.metadata');

        return [
            $m['source_key'] => ['$eq' => $m['source_value']],
            $m['model_type_key'] => ['$eq' => $modelClass],
        ];
    }

    private function matchToSource(QueryVectorMatch $match): RagSourceChunk
    {
        $meta = is_array($match->metadata) ? $match->metadata : [];
        $textKey = (string) config('knowledge.metadata.chunk_text_key');
        $text = isset($meta[$textKey]) && is_string($meta[$textKey]) ? $meta[$textKey] : '';

        return new RagSourceChunk($match->id, $text, $match->score, $meta);
    }
}
