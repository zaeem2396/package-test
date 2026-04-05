<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Verifies Vectora Studio HTTP routes respond without errors for expected status codes.
 */
#[Group('vectora-studio-api')]
class VectoraStudioApiEndpointsTest extends TestCase
{
    public function test_get_vectora_studio_page_ok(): void
    {
        $this->get(route('vectora.studio'))->assertOk();
    }

    public function test_stats_returns_503_when_pinecone_not_configured(): void
    {
        config(['pinecone.api_key' => '']);

        $this->getJson('/vectora/api/stats')
            ->assertStatus(503)
            ->assertJsonPath('ok', false);
    }

    public function test_upsert_query_delete_return_503_when_pinecone_not_configured(): void
    {
        config(['pinecone.api_key' => '']);

        $this->postJson('/vectora/api/upsert', [
            'id' => 'x',
            'text' => 'hello',
        ])->assertStatus(503)->assertJsonPath('ok', false);

        $this->postJson('/vectora/api/query', [
            'text' => 'hello',
        ])->assertStatus(503)->assertJsonPath('ok', false);

        $this->postJson('/vectora/api/delete', [
            'ids' => 'a,b',
        ])->assertStatus(503)->assertJsonPath('ok', false);
    }

    public function test_stats_returns_422_for_unknown_connection(): void
    {
        $this->fakePineconeGateConfig();

        $this->getJson('/vectora/api/stats?connection=does-not-exist')
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_upsert_validation_errors_without_id_or_vector_source(): void
    {
        $this->fakePineconeGateConfig();

        $this->postJson('/vectora/api/upsert', [])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->postJson('/vectora/api/upsert', [
            'id' => 'doc-1',
        ])->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonFragment(['message' => 'Provide either `text` (for embeddings) or `vector` (JSON array of floats).']);

        $this->postJson('/vectora/api/upsert', [
            'id' => 'doc-1',
            'text' => 'hello',
            'vector' => '[0.1]',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Send only one of `text` or `vector`, not both.']);
    }

    public function test_upsert_rejects_invalid_vector_json(): void
    {
        $this->fakePineconeGateConfig();

        $this->postJson('/vectora/api/upsert', [
            'id' => 'doc-1',
            'vector' => 'not-json',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => '`vector` must be a JSON array of numbers.']);
    }

    public function test_query_requires_text(): void
    {
        $this->fakePineconeGateConfig();

        $this->postJson('/vectora/api/query', [])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_delete_requires_non_empty_ids(): void
    {
        $this->fakePineconeGateConfig();

        $response = $this->postJson('/vectora/api/delete', [
            'ids' => '  ',
        ]);

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $json = $response->json();
        $trimmedEmpty = isset($json['errors']['ids']);
        $splitEmpty = ($json['message'] ?? '') === 'Provide at least one vector id.';
        $this->assertTrue($trimmedEmpty || $splitEmpty, 'Expected validation error on ids or empty-id message.');
    }

    /**
     * Satisfies {@see VectoraStudioController::pineconeConfigured()} without calling Pinecone.
     */
    private function fakePineconeGateConfig(): void
    {
        config([
            'pinecone.api_key' => 'test-key-not-used-for-network-in-these-tests',
            'pinecone.indexes' => [
                'default' => [
                    'host' => 'https://invalid.invalid',
                    'namespace' => '',
                ],
            ],
        ]);
    }
}
