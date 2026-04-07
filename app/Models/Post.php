<?php

namespace App\Models;

use App\Services\KnowledgeRetrievalService;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Vectora\Pinecone\DTO\QueryVectorsResult;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
    ];

    /**
     * Semantic search over ingested knowledge chunks for this model type (Pinecone).
     *
     * @param  array<string, mixed>|null  $additionalFilter  Merged with kb_source / model_type filters
     */
    public static function semanticSearch(string $query, int $topK = 10, ?array $additionalFilter = null): QueryVectorsResult
    {
        return app(KnowledgeRetrievalService::class)->queryForModel(static::class, $query, $topK, $additionalFilter);
    }

    /**
     * @return Collection<int, static>
     */
    public static function semanticSearchModels(string $query, int $topK = 10, ?array $additionalFilter = null): Collection
    {
        $result = static::semanticSearch($query, $topK, $additionalFilter);
        $ids = collect($result->matches)
            ->map(function ($m) {
                $meta = is_array($m->metadata) ? $m->metadata : [];

                return isset($meta['model_id']) ? (int) $meta['model_id'] : null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return static::query()->whereIn('id', $ids)->get();
    }
}
