<?php

namespace Tests\Feature;

use LaravelNats\Laravel\Facades\Nats;
use Tests\TestCase;

class NatsPublishTest extends TestCase
{
    public function test_publish_redirects_back_with_success(): void
    {
        Nats::shouldReceive('publish')
            ->once()
            ->with('test.subject', \Mockery::type('array'));

        $response = $this->post(route('nats.publish'), [
            'subject' => 'test.subject',
            'payload' => '{"key": "value"}',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_publish_validates_subject_required(): void
    {
        $response = $this->post(route('nats.publish'), [
            'subject' => '',
            'payload' => '{}',
        ]);

        $response->assertSessionHasErrors('subject');
    }
}
