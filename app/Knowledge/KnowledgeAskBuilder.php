<?php

namespace App\Knowledge;

use App\Dto\RagAnswerPayload;
use App\Models\Post;
use App\Services\KnowledgeRagService;
use InvalidArgumentException;

/**
 * Fluent RAG API: {@see KnowledgeAskGateway::ask()}.
 *
 * Example:
 *   Knowledge::ask('What is the refund policy?')
 *       ->from(Post::class)
 *       ->topK(5)
 *       ->withSources()
 *       ->answer();
 */
final class KnowledgeAskBuilder
{
    private string $modelClass = Post::class;

    private int $topK;

    private bool $withSources = true;

    private ?string $llmDriver = null;

    public function __construct(
        private readonly string $question,
        private readonly KnowledgeRagService $rag,
    ) {
        $this->topK = (int) config('knowledge.default_top_k', 5);
    }

    /**
     * @param  class-string  $modelClass  Must match ingested {@see Post} (or future models).
     */
    public function from(string $modelClass): self
    {
        if ($modelClass !== Post::class) {
            throw new InvalidArgumentException(sprintf(
                'This PoC only ingests %s. Given: %s',
                Post::class,
                $modelClass
            ));
        }
        $clone = clone $this;
        $clone->modelClass = $modelClass;

        return $clone;
    }

    public function topK(int $topK): self
    {
        if ($topK < 1) {
            throw new InvalidArgumentException('topK must be at least 1.');
        }
        $clone = clone $this;
        $clone->topK = $topK;

        return $clone;
    }

    public function withSources(bool $include = true): self
    {
        $clone = clone $this;
        $clone->withSources = $include;

        return $clone;
    }

    public function llm(?string $driver): self
    {
        $clone = clone $this;
        $clone->llmDriver = $driver;

        return $clone;
    }

    public function answer(): RagAnswerPayload
    {
        return $this->rag->answer(
            $this->question,
            $this->modelClass,
            $this->topK,
            $this->withSources,
            $this->llmDriver,
        );
    }
}
