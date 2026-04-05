<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Laravel\Facades\Pinecone;

class PineconeTestCommand extends Command
{
    private const SEED_SOURCE = 'vectora:test-pinecone-seed';

    private const SEED_PREFIX = 'vectora-seed';

    protected $signature = 'vectora:test-pinecone
                            {--connection= : Named Pinecone index (default from config)}
                            {--cleanup : Remove seed vectors by id after a successful query}
                            {--top-k=8 : Query topK (within filtered seed records)}
                            {--query= : Optional query text; default is a paraphrase aimed at the Laravel queues doc}';

    protected $description = 'Smoke-test Vectora: upsert real text embeddings, run semantic query (filtered to seeds), optional cleanup';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: null;

        try {
            $store = Pinecone::connection($connection);
        } catch (\Throwable $e) {
            $this->error('Failed to resolve Pinecone connection: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Step 1/4: describe_index_stats');
        $stats = $store->describeIndexStats();
        $this->line(sprintf(
            '  dimension=%d totalVectorCount=%d metric=%s',
            $stats->dimension,
            $stats->totalVectorCount,
            $stats->metric ?? 'n/a'
        ));

        $dim = $stats->dimension;
        if ($dim < 1) {
            $this->error('Index reported invalid dimension.');

            return self::FAILURE;
        }

        $docs = $this->seedDocuments();
        $ids = array_column($docs, 'id');

        $vectors = [];
        foreach ($docs as $doc) {
            try {
                $values = Pinecone::embed($doc['text']);
            } catch (\Throwable $e) {
                $this->error('Embedding failed: '.$e->getMessage());

                return self::FAILURE;
            }
            if (count($values) !== $dim) {
                $this->error(sprintf(
                    'Embedding length is %d but this index expects %d. Set PINECONE_EMBEDDING_DRIVER / PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS or OPENAI_* so dimensions match the index.',
                    count($values),
                    $dim
                ));

                return self::FAILURE;
            }
            $vectors[] = new VectorRecord(
                id: $doc['id'],
                values: $values,
                metadata: [
                    'source' => self::SEED_SOURCE,
                    'title' => $doc['title'],
                    'topic' => $doc['topic'],
                ],
            );
        }

        $this->newLine();
        $this->info('Step 2/4: upsert '.count($vectors).' text records (embedded via Pinecone::embed)');
        foreach ($docs as $doc) {
            $this->line('  • '.$doc['id'].' — '.$doc['title']);
        }
        $upsert = $store->upsert(new UpsertVectorsRequest($vectors));
        $this->line('  upsertedCount='.$upsert->upsertedCount);

        $usesDeterministicEmbeddings = (string) config('pinecone.embeddings.default', 'deterministic') === 'deterministic';
        if ($usesDeterministicEmbeddings) {
            $this->newLine();
            $this->warn(
                'Embedding driver is `deterministic`: vectors are hash-based, not semantic. '
                .'Rankings below are not meaningful for search quality — set PINECONE_EMBEDDING_DRIVER=openai (and matching index dimension) for real retrieval tests.'
            );
        }

        $queryText = $this->option('query') ?: 'How can I run work in the background in a Laravel application instead of blocking the HTTP request?';
        $this->newLine();
        $this->info('Step 3/4: semantic query');
        $this->line('  query text: '.$queryText);

        try {
            $queryVector = Pinecone::embed($queryText);
        } catch (\Throwable $e) {
            $this->error('Query embedding failed: '.$e->getMessage());

            return self::FAILURE;
        }
        if (count($queryVector) !== $dim) {
            $this->error(sprintf('Query embedding length %d does not match index dimension %d.', count($queryVector), $dim));

            return self::FAILURE;
        }

        $topK = max(1, (int) $this->option('top-k'));
        $filter = ['source' => ['$eq' => self::SEED_SOURCE]];
        $result = $store->query(new QueryVectorsRequest(
            vector: $queryVector,
            topK: $topK,
            namespace: null,
            filter: $filter,
            includeMetadata: true,
            includeValues: false,
        ));

        $matches = $result->matches;
        if ($matches === []) {
            $this->warn('No matches with metadata filter (field may not be indexed for filtering). Retrying without filter and keeping only seed ids.');
            $wideK = max($topK, 50);
            $result = $store->query(new QueryVectorsRequest(
                vector: $queryVector,
                topK: $wideK,
                namespace: null,
                filter: null,
                includeMetadata: true,
                includeValues: false,
            ));
            $idSet = array_flip($ids);
            $matches = array_values(array_filter(
                $result->matches,
                static fn ($m) => isset($idSet[$m->id])
            ));
        }

        usort($matches, static fn ($a, $b) => $b->score <=> $a->score);

        $rows = [];
        foreach ($matches as $m) {
            $meta = $m->metadata ?? [];
            $rows[] = [
                $m->id,
                number_format($m->score, 4),
                isset($meta['title']) ? (string) $meta['title'] : '',
                isset($meta['topic']) ? (string) $meta['topic'] : '',
            ];
        }
        $this->table(['id', 'score', 'title', 'topic'], $rows);

        if ($matches === []) {
            $this->error('Query returned no seed matches. Try a larger index topK or verify embeddings dimension matches the index.');

            return self::FAILURE;
        }

        $expectedId = self::SEED_PREFIX.'-laravel-queues';
        $topId = $matches[0]->id;
        if ($usesDeterministicEmbeddings) {
            $this->comment('Rank order is arbitrary with deterministic embeddings; use OpenAI (or similar) to judge semantic ranking.');
        } elseif ($topId === $expectedId) {
            $this->info('Top match is the Laravel queues seed — semantic search looks good.');
        } else {
            $rank = null;
            foreach ($matches as $i => $m) {
                if ($m->id === $expectedId) {
                    $rank = $i + 1;
                    break;
                }
            }
            if ($rank !== null) {
                $this->warn('Expected Laravel queues doc is rank #'.$rank.' (not #1). Embeddings or query wording may differ; inspect the table.');
            } else {
                $this->warn('Laravel queues doc not in top results for this query; inspect embeddings and query.');
            }
        }

        if ($this->option('cleanup')) {
            $this->newLine();
            $this->info('Step 4/4: delete seed vectors by id');
            $store->delete(new DeleteVectorsRequest(
                namespace: null,
                ids: $ids,
            ));
            $this->line('  deleted '.count($ids).' ids');
        } else {
            $this->newLine();
            $this->comment('Skipped cleanup. Re-run with --cleanup to delete seed vectors.');
        }

        $this->newLine();
        $this->info('Also try: php artisan pinecone:sync · Vectora Studio semantic search with your own text.');

        return self::SUCCESS;
    }

    /**
     * Four short “real” passages so semantic search differs between them.
     *
     * @return list<array{id: string, title: string, topic: string, text: string}>
     */
    private function seedDocuments(): array
    {
        return [
            [
                'id' => self::SEED_PREFIX.'-laravel-queues',
                'title' => 'Laravel queues and Horizon',
                'topic' => 'laravel',
                'text' => 'Laravel queues let you defer slow work such as sending mail, calling APIs, or generating reports. '
                    .'You dispatch jobs onto connections backed by database, Redis, Amazon SQS, or other drivers. '
                    .'Laravel Horizon provides a dashboard for Redis queues: you can monitor throughput, recent jobs, failed jobs, and retries.',
            ],
            [
                'id' => self::SEED_PREFIX.'-pinecone-vectors',
                'title' => 'Pinecone vector search',
                'topic' => 'pinecone',
                'text' => 'Pinecone is a managed vector database for similarity search. You upsert embedding vectors with optional metadata, '
                    .'then query with another embedding to retrieve nearest neighbors by cosine or other metrics. '
                    .'Namespaces partition data inside an index.',
            ],
            [
                'id' => self::SEED_PREFIX.'-php-readonly',
                'title' => 'PHP readonly properties',
                'topic' => 'php',
                'text' => 'PHP 8.1 introduced readonly class properties: they may be assigned once, typically in the constructor, '
                    .'and cannot be reassigned afterward. This helps model immutable value objects and data transfer objects.',
            ],
            [
                'id' => self::SEED_PREFIX.'-openai-embeddings',
                'title' => 'OpenAI text embeddings',
                'topic' => 'embeddings',
                'text' => 'OpenAI offers embedding models that map text into dense vectors for semantic search and clustering. '
                    .'You send a string to the embeddings API and receive a float array whose length depends on the model. '
                    .'Those vectors are often stored in Pinecone or similar stores for retrieval.',
            ],
        ];
    }
}
