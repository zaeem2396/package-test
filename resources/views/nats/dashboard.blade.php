@extends('layouts.app')

@section('title', 'NATS Dashboard')

@section('content')
<h1>NATS Dashboard</h1>

<div class="card">
    <h2>Recent activity</h2>
    <p class="activity-meta">Every publish, job dispatch, job completion, and request/reply is logged here. Run the queue worker to see job output.</p>
    @if($recentActivities->isEmpty())
        <p class="activity-meta">No activity yet. Publish a message, dispatch a job, or try request/reply.</p>
    @else
        <ul class="activity-list">
            @foreach($recentActivities as $a)
                <li class="activity-item">
                    <span class="activity-type {{ $a->type }}">{{ $a->type }}</span>
                    <div>
                        <div class="activity-summary">{{ $a->summary }}</div>
                        <div class="activity-meta">{{ $a->created_at->diffForHumans() }} · {{ $a->created_at->format('Y-m-d H:i:s') }}</div>
                        @if($a->payload && count($a->payload) > 0)
                            <details class="activity-payload"><summary>Payload</summary><pre>{{ json_encode($a->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <p><a href="{{ route('nats.activity') }}" class="btn btn-secondary">View full activity feed</a></p>
    @endif
</div>

@if($jetstreamAvailable && $accountInfo)
<div class="card">
    <h2>JetStream status</h2>
    <p>JetStream is available. Account info (memory, storage, streams) is loaded.</p>
</div>
@else
<div class="card">
    <p>JetStream is not available or NATS is not connected. Ensure NATS is running with <code>--jetstream</code>.</p>
</div>
@endif

<div class="card">
    <h2>Publish message</h2>
    <form action="{{ route('nats.publish') }}" method="post">
        @csrf
        <label>Subject</label>
        <input type="text" name="subject" value="demo.events" required>
        <label>Payload (JSON)</label>
        <textarea name="payload" placeholder='{"message": "Hello NATS"}'>{"message": "Hello NATS"}</textarea>
        <button type="submit">Publish</button>
    </form>
</div>

<div class="card">
    <h2>Request / Reply</h2>
    <p class="mb-2">Send a request and wait for a reply (requires a responder on the subject).</p>
    <form action="{{ route('nats.request-reply') }}" method="post">
        @csrf
        <label>Subject</label>
        <input type="text" name="subject" value="demo.ping" required>
        <label>Payload (JSON)</label>
        <textarea name="payload" placeholder='{}'>{}</textarea>
        <button type="submit">Send request</button>
    </form>
    @if(session('reply'))
        <p class="mt-2"><strong>Reply:</strong></p>
        <pre>{{ json_encode(session('reply'), JSON_PRETTY_PRINT) }}</pre>
    @endif
</div>

<div class="card">
    <h2>Queue: dispatch job</h2>
    <p class="mb-2">Dispatch a job to the NATS queue. Run <code>php artisan queue:work nats</code> to process.</p>
    <form action="{{ route('nats.queue.dispatch') }}" method="post">
        @csrf
        <label>Order ID</label>
        <input type="text" name="order_id" value="ORD-{{ time() }}" required>
        <label>Amount</label>
        <input type="number" name="amount" value="99.99" step="0.01">
        <button type="submit">Dispatch job</button>
    </form>
</div>

<div class="card">
    <h2>Queue: delayed job (JetStream)</h2>
    <p class="mb-2">Schedule a job to run after a delay (requires JetStream).</p>
    <form action="{{ route('nats.queue.delayed') }}" method="post">
        @csrf
        <label>Delay (seconds)</label>
        <input type="number" name="delay_seconds" value="30" min="1" max="3600">
        <label>Message</label>
        <input type="text" name="message" value="Reminder" placeholder="Reminder text">
        <button type="submit">Schedule delayed job</button>
    </form>
</div>

<div class="card">
    <h2>Queue: failing job (DLQ demo)</h2>
    <p class="mb-2">Dispatch a job that will fail. It will be recorded in <code>failed_jobs</code> and can be sent to the Dead Letter Queue.</p>
    <form action="{{ route('nats.queue.failing') }}" method="post">
        @csrf
        <button type="submit">Dispatch failing job</button>
    </form>
</div>

<div class="card">
    <h2>Quick links</h2>
    <ul class="links">
        <li><a href="{{ route('nats.activity') }}">Activity feed</a> – all events (publishes, jobs, request/reply)</li>
        <li><a href="{{ route('nats.streams') }}">JetStream streams</a> – list streams</li>
        <li><a href="{{ route('nats.failed-jobs') }}">Failed jobs</a> – list failed queue jobs</li>
    </ul>
</div>
@endsection
