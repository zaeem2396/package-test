<?php

namespace Tests\Unit;

use App\Jobs\FailingDemoJob;
use App\Models\NatsActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FailingDemoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_throws_on_handle(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FailingDemoJob');

        $job = new FailingDemoJob();
        $job->handle();
    }

    public function test_job_logs_activity_on_failed(): void
    {
        $job = new FailingDemoJob();
        $job->failed(new RuntimeException('FailingDemoJob: intentional failure for DLQ demo.'));

        $this->assertSame(1, NatsActivity::count());
        $activity = NatsActivity::first();
        $this->assertSame('job_failed', $activity->type);
        $this->assertStringContainsString('FailingDemoJob', $activity->summary);
    }
}
