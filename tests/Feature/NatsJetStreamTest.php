<?php

namespace Tests\Feature;

use LaravelNats\Laravel\Facades\Nats;
use Tests\TestCase;

class NatsJetStreamTest extends TestCase
{
    public function test_streams_page_handles_jetstream_unavailable(): void
    {
        $js = \Mockery::mock();
        $js->shouldReceive('isAvailable')->andReturn(false);
        Nats::shouldReceive('jetstream')->andReturn($js);

        $response = $this->get(route('nats.streams'));

        $response->assertStatus(200);
        $response->assertSee('JetStream is not available', false);
    }

    public function test_streams_page_shows_streams_when_available(): void
    {
        $js = \Mockery::mock();
        $js->shouldReceive('isAvailable')->andReturn(true);
        $js->shouldReceive('listStreams')->with(\Mockery::on(fn ($o) => $o === 0))->andReturn([
            'streams' => [],
            'total' => 0,
        ]);
        Nats::shouldReceive('jetstream')->andReturn($js);

        $response = $this->get(route('nats.streams'));

        $response->assertStatus(200);
    }
}
