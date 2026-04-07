<?php

namespace App\Services;

use App\Jobs\IngestPostJob;
use App\Models\Post;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\IngestedChunk;
use Vectora\Pinecone\Laravel\Facades\Vector;
use Vectora\Pinecone\Laravel\VectorStoreManager;

/**
 * Chunk + embed + upsert posts via Vectora {@see Vector::ingest()} (uses EmbeddingDriver + VectorStoreContract).
 */
final class KnowledgeIngestionService
{
    public function __construct(
        private readonly VectorStoreManager $stores,
    ) {}

    public function ingestPost(Post $post, bool $queue = false): int
    {
        if ($queue) {
            IngestPostJob::dispatch($post->id);

            return 0;
        }

        $this->purgePostVectors($post);

        $text = trim($post->title."\n\n".$post->content);
        if ($text === '') {
            return 0;
        }

        $m = config('knowledge.metadata');
        $chunkSize = (int) config('knowledge.chunk_size');
        $overlap = (int) config('knowledge.chunk_overlap');

        $count = Vector::ingest()
            ->fromString($text, [
                $m['source_key'] => $m['source_value'],
                $m['model_type_key'] => Post::class,
                $m['model_id_key'] => $post->id,
            ])
            ->enrich(function (IngestedChunk $chunk) use ($m): IngestedChunk {
                return $chunk->withMetadata([
                    $m['chunk_index_key'] => $chunk->index,
                    $m['chunk_text_key'] => mb_substr($chunk->text, 0, 12000),
                ]);
            })
            ->chunks($chunkSize, $overlap)
            ->syncUpsert('kb-post-'.$post->id);

        return $count;
    }

    public function ingestAll(bool $queue = false): void
    {
        Post::query()->orderBy('id')->each(function (Post $post) use ($queue): void {
            $this->ingestPost($post, $queue);
        });
    }

    private function purgePostVectors(Post $post): void
    {
        $m = config('knowledge.metadata');
        $store = $this->stores->driver();

        $store->delete(new DeleteVectorsRequest(
            namespace: null,
            ids: null,
            filter: [
                $m['source_key'] => ['$eq' => $m['source_value']],
                $m['model_id_key'] => ['$eq' => $post->id],
            ],
            deleteAll: false,
        ));
    }
}
