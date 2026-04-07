<?php

namespace App\Dto;

use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\RagSourceChunk;

/**
 * @param  list<RagSourceChunk>  $chunks
 */
final readonly class KnowledgeChunkResult
{
    /**
     * @param  list<RagSourceChunk>  $chunks
     */
    public function __construct(
        public array $chunks,
        public float $embedMs,
        public float $queryMs,
        public QueryVectorsResult $raw,
    ) {}
}
