<?php

namespace Tests\Feature;

use LaravelNats\Laravel\Facades\Nats;
use Tests\TestCase;

class NatsRequestReplyTest extends TestCase
{
    public function test_request_reply_validates_subject(): void
    {
        $response = $this->post(route('nats.request-reply'), [
            'subject' => '',
            'payload' => '{}',
        ]);

        $response->assertSessionHasErrors('subject');
    }

    public function test_request_reply_returns_error_when_no_responder(): void
    {
        Nats::shouldReceive('request')
            ->once()
            ->andThrow(new \RuntimeException('No responders'));

        $response = $this->post(route('nats.request-reply'), [
            'subject' => 'test.ping',
            'payload' => '{}',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
