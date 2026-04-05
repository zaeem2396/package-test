<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pinecone API key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('PINECONE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Data-plane API version (X-Pinecone-Api-Version)
    |--------------------------------------------------------------------------
    */
    'api_version' => env('PINECONE_API_VERSION', '2025-10'),

    /*
    |--------------------------------------------------------------------------
    | Control plane (index create / describe / delete)
    |--------------------------------------------------------------------------
    */
    'control_plane' => [
        'url' => env('PINECONE_CONTROL_PLANE_URL', 'https://api.pinecone.io'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default logical index name (see indexes)
    |--------------------------------------------------------------------------
    */
    'default' => env('PINECONE_INDEX', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Named index connections (host + optional default namespace)
    |--------------------------------------------------------------------------
    |
    | If empty, legacy `host` / `namespace` below are mapped to the connection
    | named by `default` (PINECONE_INDEX), not always the string "default".
    |
    */
    'indexes' => [
        'default' => [
            'host' => env('PINECONE_HOST', ''),
            'namespace' => env('PINECONE_NAMESPACE', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy single-index host/namespace (used when indexes is empty)
    |--------------------------------------------------------------------------
    */
    'host' => env('PINECONE_HOST', ''),
    'namespace' => env('PINECONE_NAMESPACE', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP / resilience (Core PineconeHttpTransport)
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => (float) env('PINECONE_HTTP_TIMEOUT', 30),
        'connect_timeout' => (float) env('PINECONE_CONNECT_TIMEOUT', 10),
        'retries' => (int) env('PINECONE_HTTP_RETRIES', 4),
        'retry_delay_ms' => (int) env('PINECONE_RETRY_DELAY_MS', 250),
        'max_delay_ms' => (int) env('PINECONE_MAX_DELAY_MS', 10_000),
        'respect_retry_after' => filter_var(env('PINECONE_RESPECT_RETRY_AFTER', true), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue defaults for vector jobs
    |--------------------------------------------------------------------------
    |
    | Omit PINECONE_QUEUE (or leave null) so jobs use the selected connection’s
    | default queue name instead of forcing Laravel’s global "default" queue.
    |
    */
    'queue' => [
        'connection' => env('PINECONE_QUEUE_CONNECTION'),
        'queue' => env('PINECONE_QUEUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional request logging (ObservabilityHooks → Laravel Log)
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => filter_var(env('PINECONE_LOG_REQUESTS', false), FILTER_VALIDATE_BOOL),
        'channel' => env('PINECONE_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Developer debug (verbose HTTP logging — avoid in production)
    |--------------------------------------------------------------------------
    |
    | When enabled, logs request/response previews (truncated) at debug level.
    | Use with PINECONE_LOG_REQUESTS for full request/response line logging.
    |
    */
    'debug' => [
        'enabled' => filter_var(env('PINECONE_DEBUG', false), FILTER_VALIDATE_BOOL),
        'channel' => env('PINECONE_DEBUG_CHANNEL'),
        'body_preview_max' => (int) env('PINECONE_DEBUG_BODY_MAX', 2048),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query result cache (Laravel Cache for VectorStoreContract::query)
    |--------------------------------------------------------------------------
    |
    | Caches query responses by hashed request body + index fingerprint.
    | Invalidate by TTL or `php artisan cache:clear` / store flush.
    |
    */
    'query_cache' => [
        'enabled' => filter_var(env('PINECONE_QUERY_CACHE', false), FILTER_VALIDATE_BOOL),
        'store' => env('PINECONE_QUERY_CACHE_STORE'),
        'prefix' => env('PINECONE_QUERY_CACHE_PREFIX', 'vectora.pinecone.query'),
        'ttl' => env('PINECONE_QUERY_CACHE_TTL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP metrics / tracing (Phase 6)
    |--------------------------------------------------------------------------
    |
    | When enabled, dispatches PineconeHttpRequestFinished after each logical HTTP
    | call (including correlation id and duration). Listeners can forward to APM or logs.
    |
    */
    'metrics' => [
        'enabled' => filter_var(env('PINECONE_METRICS', false), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embeddings (text → vector)
    |--------------------------------------------------------------------------
    |
    | Drivers: `deterministic` (hash-based, no API), `openai` (OpenAI embeddings).
    | When `cache.enabled` is true, results are keyed by SHA-256 of input text.
    |
    */
    'embeddings' => [
        'default' => env('PINECONE_EMBEDDING_DRIVER', 'deterministic'),
        'cache' => [
            'enabled' => filter_var(env('PINECONE_EMBEDDING_CACHE', false), FILTER_VALIDATE_BOOL),
            'store' => env('PINECONE_EMBEDDING_CACHE_STORE'),
            'prefix' => env('PINECONE_EMBEDDING_CACHE_PREFIX', 'vectora.embeddings'),
            'ttl' => env('PINECONE_EMBEDDING_CACHE_TTL'),
        ],
        'drivers' => [
            'deterministic' => [
                'dimensions' => (int) env('PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS', 8),
            ],
            'openai' => [
                'api_key' => env('OPENAI_API_KEY', env('PINECONE_OPENAI_API_KEY', '')),
                'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
                'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
                'batch_size' => (int) env('PINECONE_OPENAI_EMBEDDING_BATCH', 100),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent (HasEmbeddings)
    |--------------------------------------------------------------------------
    |
    | `default_sync`: `sync` upserts inline on model events; `queued` dispatches
    | SyncModelEmbeddingJob / DeleteVectorsJob. Override per model via
    | Embeddable::vectorEmbeddingSyncMode().
    |
    */
    'eloquent' => [
        'default_sync' => env('PINECONE_ELOQUENT_SYNC', 'queued'),
    ],
];
