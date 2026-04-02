@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <h1 class="h3 mb-4">Orders</h1>
    <p class="text-muted">Workflow-driven e-commerce demo. Create an order, then run <code>php artisan conductor:work</code> to execute tasks.</p>

    <div class="card mb-4">
        <div class="card-header">Create order</div>
        <div class="card-body">
            <form method="post" action="{{ route('orders.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Amount (USD)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', '49.99') }}"
                           class="form-control @error('amount') is-invalid @enderror" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror" required placeholder="customer@example.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">POST /orders</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">All orders</div>
        <div class="table-responsive mb-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Amount</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Current step</th>
                    <th>Retry count</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>${{ number_format((float) $order->amount, 2) }}</td>
                        <td>{{ $order->email }}</td>
                        <td>
                            @php
                                $badge = match ($order->status) {
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'processing' => 'primary',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $order->status }}</span>
                        </td>
                        <td><code>{{ $order->current_step ?? '—' }}</code></td>
                        <td>{{ $order->retry_count }}</td>
                        <td><a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No orders yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-body">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
