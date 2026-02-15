@extends('layouts.app')

@section('title', 'Order ' . $order->reference)

@section('content')
    <h1>Order {{ $order->reference }}</h1>
    <div class="card">
        <table>
            <tr><th>Reference</th><td>{{ $order->reference }}</td></tr>
            <tr><th>Customer</th><td>{{ $order->customer_email }}</td></tr>
            <tr><th>Amount</th><td>{{ number_format($order->amount, 2) }}</td></tr>
            <tr><th>Status</th><td>{{ $order->status }}</td></tr>
            <tr><th>Created</th><td>{{ $order->created_at->toIso8601String() }}</td></tr>
            @if($order->shipped_at)
                <tr><th>Shipped</th><td>{{ $order->shipped_at->toIso8601String() }}</td></tr>
            @endif
        </table>
        @if($order->payment_response)
            <h2>Payment response</h2>
            <pre>{{ json_encode($order->payment_response, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
    <p><a href="{{ route('orders.index') }}" class="btn btn-secondary">Back to orders</a></p>
@endsection
