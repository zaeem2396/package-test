<?php

namespace Tests\Feature;

use Tests\TestCase;

class VectoraStudioPageTest extends TestCase
{
    public function test_vectora_studio_page_renders(): void
    {
        $response = $this->get(route('vectora.studio'));

        $response->assertOk();
        $response->assertSee('Vectora Studio', false);
    }

    public function test_vectora_api_stats_without_configuration_returns_503(): void
    {
        config(['pinecone.api_key' => '']);

        $response = $this->getJson('/vectora/api/stats');

        $response->assertStatus(503);
        $response->assertJsonFragment(['ok' => false]);
    }
}
