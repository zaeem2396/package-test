<?php

namespace App\Http\Controllers;

use App\Jobs\FulfillOrderJob;
use App\Models\NatsActivity;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LaravelNats\Laravel\Facades\Nats;

class OrderController extends Controller
{
    /**
     * List orders (for PoC UI).
     */
    public function index()
    {
        $orders = Order::orderByDesc('created_at')->limit(50)->get();
        return view('orders.index', ['orders' => $orders]);
    }

    /**
     * Create order form.
     */
    public function create()
    {
        return view('orders.create');
    }

    /**
     * POST /orders – Event-driven order flow:
     * 1. Create order, publish orders.created
     * 2. Request payments.validate (RPC)
     * 3. On approval, dispatch FulfillOrderJob via NATS queue
     * 4. Job will publish orders.shipped and metrics.orders (analytics)
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_email' => 'required|email',
            'amount' => 'required|numeric|min:0',
        ]);

        $order = Order::create([
            'reference' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_email' => $request->input('customer_email'),
            'amount' => $request->input('amount'),
            'status' => 'created',
        ]);

        $payload = [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'customer_email' => $order->customer_email,
            'amount' => (float) $order->amount,
            'created_at' => $order->created_at->toIso8601String(),
        ];

        // 1. Publish orders.created (event chaining: downstream subscribers see it)
        Nats::publish('orders.created', $payload);
        $this->logActivity('orders.created', $order, $payload);

        // 2. Request/Reply: payments.validate (payment service replies with approval)
        try {
            $paymentReply = Nats::request('payments.validate', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'amount' => (float) $order->amount,
            ], timeout: 5.0);
            $paymentBody = $paymentReply->getDecodedPayload();
            $approved = $paymentBody['approved'] ?? false;
            $order->markPaid($paymentBody);
            $this->logActivity('payments.validate reply', $order, $paymentBody);
        } catch (\Throwable $e) {
            return back()->with('error', 'Payment validation failed: ' . $e->getMessage())->withInput();
        }

        if (!$approved) {
            return back()->with('error', 'Payment not approved.')->withInput();
        }

        // 3. Dispatch job on NATS queue (worker will process and publish orders.shipped)
        FulfillOrderJob::dispatch($order)->onConnection('nats');
        $this->logActivity('job_dispatched FulfillOrderJob', $order, ['order_id' => $order->id]);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Order {$order->reference} created, paid, and queued for fulfillment.");
    }

    public function show(Order $order)
    {
        return view('orders.show', ['order' => $order]);
    }

    private function logActivity(string $type, Order $order, array $payload): void
    {
        try {
            NatsActivity::log($type, "Order {$order->reference}: {$type}", array_merge(['order_id' => $order->id], $payload));
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
