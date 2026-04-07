<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chunking (character windows; ~500 tokens ≈ 2000 chars for English prose)
    |--------------------------------------------------------------------------
    */
    'chunk_size' => (int) env('KNOWLEDGE_CHUNK_SIZE', 2000),

    'chunk_overlap' => (int) env('KNOWLEDGE_CHUNK_OVERLAP', 200),

    /*
    |--------------------------------------------------------------------------
    | Pinecone metadata (must match KnowledgeChunkRetriever filters)
    |--------------------------------------------------------------------------
    */
    'metadata' => [
        'source_key' => 'kb_source',
        'source_value' => 'post',
        'model_type_key' => 'model_type',
        'model_id_key' => 'model_id',
        'chunk_index_key' => 'chunk_index',
        'chunk_text_key' => 'chunk_text',
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG defaults
    |--------------------------------------------------------------------------
    */
    'default_top_k' => (int) env('KNOWLEDGE_DEFAULT_TOP_K', 5),

    'system_prompt' => env(
        'KNOWLEDGE_SYSTEM_PROMPT',
        'Answer using only the provided context when possible. If context is insufficient, say so briefly.'
    ),

];
