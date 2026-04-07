<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\KnowledgeIngestionService;
use Illuminate\Console\Command;

class VectorIngestCommand extends Command
{
    protected $signature = 'vector:ingest
                            {--queue : Dispatch one queued job per post instead of inline upsert}
                            {--id= : Ingest only the post with this id}';

    protected $description = 'Chunk, embed, and upsert Post records into the vector store (Pinecone via Vectora).';

    public function handle(KnowledgeIngestionService $ingestion): int
    {
        if ((string) config('pinecone.api_key', '') === '') {
            $this->error('PINECONE_API_KEY is not set. Configure Pinecone before ingesting.');

            return self::FAILURE;
        }

        $queue = (bool) $this->option('queue');
        $id = $this->option('id');

        if ($id !== null && $id !== '') {
            $post = Post::query()->find((int) $id);
            if ($post === null) {
                $this->error('Post not found: '.$id);

                return self::FAILURE;
            }
            if ($queue) {
                $ingestion->ingestPost($post, true);
                $this->info('Queued ingestion job for post '.$post->id);
            } else {
                $n = $ingestion->ingestPost($post, false);
                $this->info('Upserted '.$n.' vectors for post '.$post->id);
            }

            return self::SUCCESS;
        }

        $total = Post::query()->count();
        if ($total === 0) {
            $this->warn('No posts to ingest. Run: php artisan db:seed --class=PostSeeder');

            return self::FAILURE;
        }

        if ($queue) {
            Post::query()->orderBy('id')->each(function (Post $post) use ($ingestion): void {
                $ingestion->ingestPost($post, true);
            });
            $this->info('Dispatched '.$total.' ingestion job(s). Ensure a queue worker is running.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $vectors = 0;
        Post::query()->orderBy('id')->each(function (Post $post) use ($ingestion, $bar, &$vectors): void {
            $vectors += $ingestion->ingestPost($post, false);
            $bar->advance();
        });
        $bar->finish();
        $this->newLine();
        $this->info('Ingestion complete. Total vector rows upserted: '.$vectors);

        return self::SUCCESS;
    }
}
