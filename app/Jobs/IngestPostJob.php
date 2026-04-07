<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\KnowledgeIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IngestPostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $postId,
    ) {}

    public function handle(KnowledgeIngestionService $ingestion): void
    {
        $post = Post::query()->find($this->postId);
        if ($post === null) {
            return;
        }

        $ingestion->ingestPost($post, queue: false);
    }
}
