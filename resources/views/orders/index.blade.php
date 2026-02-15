@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <h1>Orders</h1>
    <p><a href="{{ route('orders.create') }}" class="btn">Create order</a></p>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->reference }}</td>
                        <td>{{ $order->customer_email }}</td>
                        <td>{{ number_format($order->amount, 2) }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No orders yet. <a href="{{ route('orders.create') }}">Create one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
