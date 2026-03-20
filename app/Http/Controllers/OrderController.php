<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Conductor\Exceptions\ConductorException;
use Conductor\Laravel\Facades\Conductor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->orderByDesc('id')
            ->paginate(20);

        return view('orders.index', ['orders' => $orders]);
    }

    public function store(Request $request, OrderService $orders): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = $orders->create([
            'amount' => $validated['amount'],
            'email' => $validated['email'],
        ]);

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Order created and workflow started. Run `php artisan conductor:work` to process tasks.');
    }

    public function show(Order $order): View
    {
        $order->load('events');

        return view('orders.show', ['order' => $order]);
    }

    public function status(Order $order): JsonResponse
    {
        $order->load('events');
        $workflow = null;
        if ($order->workflow_id) {
            try {
                $workflow = Conductor::workflow()->getWorkflow($order->workflow_id, true);
            } catch (ConductorException) {
                $workflow = null;
            }
        }

        return response()->json([
            'order' => [
                'id' => $order->id,
                'amount' => $order->amount,
                'email' => $order->email,
                'status' => $order->status,
                'current_step' => $order->current_step,
                'retry_count' => $order->retry_count,
                'workflow_id' => $order->workflow_id,
                'updated_at' => $order->updated_at?->toIso8601String(),
            ],
            'events' => $order->events->map(fn ($e) => [
                'id' => $e->id,
                'step_key' => $e->step_key,
                'message' => $e->message,
                'level' => $e->level,
                'context' => $e->context,
                'created_at' => $e->created_at?->toIso8601String(),
            ]),
            'conductor_workflow' => $workflow,
        ]);
    }
}
