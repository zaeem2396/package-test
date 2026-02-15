@extends('layouts.app')

@section('title', 'Create order')

@section('content')
    <h1>Create order</h1>
    <p>This triggers the full PoC flow: orders.created → payments.validate (RPC) → NATS queue job → orders.shipped + metrics.orders.</p>
    <div class="card">
        <form action="{{ route('orders.store') }}" method="post">
            @csrf
            <label for="customer_email">Customer email</label>
            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', 'customer@example.com') }}" required>
            <label for="amount">Amount</label>
            <input type="number" id="amount" name="amount" value="{{ old('amount', '99.99') }}" step="0.01" min="0" required>
            <button type="submit">Create order</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
