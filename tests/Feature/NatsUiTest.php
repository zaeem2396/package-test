<?php

namespace Tests\Feature;

use App\Jobs\FailingDemoJob;
use App\Jobs\ProcessOrderJob;
use App\Jobs\SendReminderJob;
use Illuminate\Support\Facades\Queue;
use LaravelNats\Laravel\Facades\Nats;
use Tests\TestCase;

/**
 * UI / Acceptance tests: verify NATS demo pages render correctly, navigation works, and forms behave as expected.
 */
class NatsUiTest extends TestCase
{
    public function test_home_page_has_nats_demo_link(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('NATS Demo', false);
        $response->assertSee(route('nats.dashboard'), false);
    }

    public function test_nats_demo_link_goes_to_dashboard(): void
    {
        $response = $this->get(route('nats.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('NATS Dashboard', false);
    }

    public function test_dashboard_shows_all_feature_sections(): void
    {
        $response = $this->get(route('nats.dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Publish message', false);
        $response->assertSee('Request / Reply', false);
        $response->assertSee('Queue: dispatch job', false);
        $response->assertSee('Queue: delayed job', false);
        $response->assertSee('Queue: failing job', false);
        $response->assertSee('Quick links', false);

        $response->assertSee('demo.events', false);
        $response->assertSee('Send request', false);
        $response->assertSee('Dispatch job', false);
        $response->assertSee('Schedule delayed job', false);
        $response->assertSee('Dispatch failing job', false);
    }

    public function test_dashboard_has_navigation_links(): void
    {
        $response = $this->get(route('nats.dashboard'));
        $response->assertStatus(200);
        $response->assertSee(route('nats.streams'), false);
        $response->assertSee(route('nats.failed-jobs'), false);
    }

    public function test_dashboard_publish_form_submits_and_shows_success(): void
    {
        Nats::shouldReceive('publish')->once()->with('test.subject', \Mockery::any());

        $response = $this->post(route('nats.publish'), [
            'subject' => 'test.subject',
            'payload' => '{"hello": "world"}',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_dashboard_publish_form_shows_validation_error_when_subject_empty(): void
    {
        $response = $this->post(route('nats.publish'), [
            'subject' => '',
            'payload' => '{}',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('subject');
    }

    public function test_dashboard_queue_dispatch_form_submits_and_shows_success(): void
    {
        Queue::fake();

        $response = $this->post(route('nats.queue.dispatch'), [
            'order_id' => 'ORD-UI-TEST',
            'amount' => '123.45',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(ProcessOrderJob::class);
    }

    public function test_dashboard_queue_dispatch_form_shows_validation_error_when_order_id_empty(): void
    {
        $response = $this->post(route('nats.queue.dispatch'), [
            'order_id' => '',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('order_id');
    }

    public function test_dashboard_delayed_form_submits_and_shows_success(): void
    {
        Queue::fake();

        $response = $this->post(route('nats.queue.delayed'), [
            'delay_seconds' => '60',
            'message' => 'UI test reminder',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(SendReminderJob::class);
    }

    public function test_dashboard_failing_job_form_submits_and_shows_success(): void
    {
        Queue::fake();

        $response = $this->post(route('nats.queue.failing'), [
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(FailingDemoJob::class);
    }

    public function test_request_reply_form_shows_error_when_no_responder(): void
    {
        Nats::shouldReceive('request')
            ->once()
            ->andThrow(new \RuntimeException('No responders'));

        $response = $this->post(route('nats.request-reply'), [
            'subject' => 'test.ping',
            'payload' => '{}',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_streams_page_renders_with_heading_and_back_link(): void
    {
        $js = \Mockery::mock();
        $js->shouldReceive('isAvailable')->andReturn(false);
        Nats::shouldReceive('jetstream')->andReturn($js);

        $response = $this->get(route('nats.streams'));
        $response->assertStatus(200);
        $response->assertSee('JetStream Streams', false);
        $response->assertSee('Back to dashboard', false);
        $response->assertSee(route('nats.dashboard'), false);
    }

    public function test_streams_page_shows_empty_or_stream_list(): void
    {
        $js = \Mockery::mock();
        $js->shouldReceive('isAvailable')->andReturn(true);
        $js->shouldReceive('listStreams')->andReturn(['streams' => [], 'total' => 0]);
        Nats::shouldReceive('jetstream')->andReturn($js);

        $response = $this->get(route('nats.streams'));
        $response->assertStatus(200);
        $response->assertSee('JetStream Streams', false);
    }

    public function test_failed_jobs_page_renders_with_heading_and_back_link(): void
    {
        $response = $this->get(route('nats.failed-jobs'));
        $response->assertStatus(200);
        $response->assertSee('Failed Jobs', false);
        $response->assertSee('Back to dashboard', false);
        $response->assertSee(route('nats.dashboard'), false);
    }

    public function test_failed_jobs_page_shows_empty_state_or_table(): void
    {
        $response = $this->get(route('nats.failed-jobs'));
        $response->assertStatus(200);
        $response->assertSee('Failed Jobs', false);
    }

    public function test_layout_nav_links_work(): void
    {
        $response = $this->get(route('nats.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('NATS Dashboard', false);
        $response->assertSee('JetStream Streams', false);
        $response->assertSee('Failed Jobs', false);

        $this->get(route('nats.streams'))->assertStatus(200);
        $this->get(route('nats.failed-jobs'))->assertStatus(200);
    }

    public function test_delayed_form_validates_delay_seconds(): void
    {
        $response = $this->post(route('nats.queue.delayed'), [
            'delay_seconds' => '99999',
            'message' => 'x',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('delay_seconds');
    }
}
