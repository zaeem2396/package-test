<?php

namespace App\Dto;

/**
 * @phpstan-type SourceRow array{id: string, score: float, model_id?: int|null, chunk_index?: int|null}
 * @phpstan-type MetaRow array{embedding_ms: float, query_ms: float, llm_ms: float, total_ms: float}
 */
final readonly class RagAnswerPayload implements \JsonSerializable
{
    /**
     * @param  list<SourceRow>  $sources
     * @param  MetaRow  $meta
     */
    public function __construct(
        public string $answer,
        public array $sources,
        public array $meta,
    ) {}

    /**
     * @return array{answer: string, sources: list<SourceRow>, meta: MetaRow}
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'sources' => $this->sources,
            'meta' => $this->meta,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
