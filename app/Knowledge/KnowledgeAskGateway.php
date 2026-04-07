<?php

namespace App\Knowledge;

use App\Services\KnowledgeRagService;

final class KnowledgeAskGateway
{
    public function __construct(
        private readonly KnowledgeRagService $rag,
    ) {}

    public function ask(string $question): KnowledgeAskBuilder
    {
        return new KnowledgeAskBuilder($question, $this->rag);
    }
}
