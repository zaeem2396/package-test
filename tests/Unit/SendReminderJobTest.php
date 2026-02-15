<?php

namespace Tests\Unit;

use App\Jobs\SendReminderJob;
use App\Models\NatsActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReminderJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_handles_successfully_and_logs_activity(): void
    {
        $job = new SendReminderJob('Test message');
        $job->handle();

        $this->assertSame(1, NatsActivity::count());
        $activity = NatsActivity::first();
        $this->assertSame('job_processed', $activity->type);
        $this->assertStringContainsString('Test message', $activity->summary);
    }
}
