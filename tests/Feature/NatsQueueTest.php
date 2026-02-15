<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrderJob;
use App\Jobs\SendReminderJob;
use App\Jobs\FailingDemoJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NatsQueueTest extends TestCase
{
    public function test_dispatch_job_redirects_with_success(): void
    {
        Queue::fake();

        $response = $this->post(route('nats.queue.dispatch'), [
            'order_id' => 'ORD-123',
            'amount' => '42.50',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(ProcessOrderJob::class, function ($job) {
            return $job->orderId === 'ORD-123' && $job->amount === 42.5;
        });
    }

    public function test_dispatch_delayed_job_redirects_with_success(): void
    {
        Queue::fake();

        $response = $this->post(route('nats.queue.delayed'), [
            'delay_seconds' => 60,
            'message' => 'Test reminder',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(SendReminderJob::class);
    }

    public function test_dispatch_failing_job_redirects_with_success(): void
    {
        Queue::fake();

        $response = $this->post(route('nats.queue.failing'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(FailingDemoJob::class);
    }

    public function test_dispatch_job_validates_order_id(): void
    {
        $response = $this->post(route('nats.queue.dispatch'), [
            'order_id' => '',
        ]);

        $response->assertSessionHasErrors('order_id');
    }
}
