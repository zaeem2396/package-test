<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Services\OrderService;
use Conductor\Laravel\Facades\Conductor;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * E-commerce order_processing PoC — HTTP + Conductor::fake() integration.
 */
final class OrderEcommerceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Conductor::fake();
    }

    public function test_orders_index_returns_success(): void
    {
        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Orders', false);
    }

    public function test_create_order_persists_row_starts_workflow_and_redirects(): void
    {
        $response = $this->post(route('orders.store'), [
            'amount' => '42.50',
            'email' => 'customer@example.org',
        ]);

        $this->assertDatabaseCount('orders', 1);
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('processing', $order->status);
        $this->assertSame('inventory_check', $order->current_step);
        $this->assertSame('fake-workflow-id', $order->workflow_id);

        $response->assertRedirect(route('orders.show', $order));

        Conductor::assertWorkflowStarted('order_processing');
        Conductor::assertWorkflowStartedWithInput('order_processing', [
            'order_id' => $order->id,
            'amount' => 42.5,
            'user_email' => 'customer@example.org',
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'step_key' => 'order_created',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'step_key' => 'workflow_started',
        ]);
    }

    public function test_create_order_validation_requires_amount_and_email(): void
    {
        $this->post(route('orders.store'), [
            'amount' => '',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['amount', 'email']);

        $this->assertDatabaseCount('orders', 0);
        Conductor::assertNoWorkflowsStarted();
    }

    public function test_order_show_displays_order(): void
    {
        $order = Order::query()->create([
            'amount' => '10.00',
            'email' => 'a@b.co',
            'status' => 'processing',
            'current_step' => 'payment_process',
            'retry_count' => 0,
            'workflow_id' => 'fake-workflow-id',
        ]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->email, false);
    }

    public function test_order_status_json_includes_order_events_and_conductor_stub(): void
    {
        $orders = $this->app->make(OrderService::class);
        $order = $orders->create([
            'amount' => 19.99,
            'email' => 'json@example.org',
        ]);

        $response = $this->getJson(route('orders.status', $order));

        $response->assertOk()
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.workflow_id', 'fake-workflow-id')
            ->assertJsonPath('conductor_workflow.workflowId', 'fake-workflow-id')
            ->assertJsonStructure([
                'order' => ['id', 'amount', 'email', 'status', 'current_step', 'retry_count', 'workflow_id'],
                'events',
                'conductor_workflow',
            ]);

        $this->assertNotEmpty($response->json('events'));
    }
}
