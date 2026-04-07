<?php

namespace Tests\Feature;

use Tests\TestCase;

class KnowledgeDemoApiTest extends TestCase
{
    public function test_ask_validation_requires_question(): void
    {
        $response = $this->postJson(route('knowledge.ask'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question']);
    }

    public function test_search_validation_requires_query(): void
    {
        $response = $this->postJson(route('knowledge.search'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }
}
