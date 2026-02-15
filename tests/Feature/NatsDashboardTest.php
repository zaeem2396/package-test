<?php

namespace Tests\Feature;

use LaravelNats\Laravel\Facades\Nats;
use Tests\TestCase;

class NatsDashboardTest extends TestCase
{
    public function test_nats_dashboard_returns_200(): void
    {
        $response = $this->get(route('nats.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('NATS Dashboard');
    }

    public function test_nats_streams_page_returns_200(): void
    {
        $response = $this->get(route('nats.streams'));
        $response->assertStatus(200);
        $response->assertSee('JetStream Streams');
    }

    public function test_nats_failed_jobs_page_returns_200(): void
    {
        $response = $this->get(route('nats.failed-jobs'));
        $response->assertStatus(200);
        $response->assertSee('Failed Jobs');
    }
}
