<?php

namespace Tests\Unit;

use App\Jobs\ProcessOrderJob;
use App\Models\NatsActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessOrderJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_handles_successfully_and_logs_activity(): void
    {
        $job = new ProcessOrderJob('ORD-1', 99.99);
        $job->handle();

        $this->assertSame(1, NatsActivity::count());
        $activity = NatsActivity::first();
        $this->assertSame('job_processed', $activity->type);
        $this->assertStringContainsString('ORD-1', $activity->summary);
        $this->assertStringContainsString('99.99', $activity->summary);
    }
}
