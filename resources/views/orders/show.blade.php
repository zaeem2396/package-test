@extends('layouts.app')

@section('title', 'Order #'.$order->id)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Order #{{ $order->id }}</h1>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Summary</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Status</span>
                        <strong id="o-status">{{ $order->status }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Amount</span>
                        <strong id="o-amount">${{ number_format((float) $order->amount, 2) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Email</span>
                        <span id="o-email" class="text-truncate ms-2" style="max-width:12rem">{{ $order->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Current step</span>
                        <code id="o-step">{{ $order->current_step ?? '—' }}</code>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Retry count</span>
                        <strong id="o-retries">{{ $order->retry_count }}</strong>
                    </li>
                    <li class="list-group-item small text-muted">
                        Workflow ID<br>
                        <code class="small" id="o-wf">{{ $order->workflow_id ?? '—' }}</code>
                    </li>
                </ul>
            </div>
            <p class="small text-muted mt-2">Page auto-refreshes data every 3s from <code>GET /orders/{{ $order->id }}/status</code>.</p>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Timeline / logs</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0" id="timeline">
                        @foreach ($order->events as $event)
                            <li class="mb-2 d-flex gap-2 border-bottom pb-2">
                                <span class="flex-shrink-0">
                                    @if($event->level === 'success')✔
                                    @elseif($event->level === 'error')✖
                                    @elseif($event->level === 'warning')⚠
                                    @elseℹ
                                    @endif
                                </span>
                                <div>
                                    <strong>{{ $event->step_key }}</strong>
                                    <span class="text-muted small">{{ $event->created_at?->format('H:i:s') }}</span>
                                    <div>{{ $event->message }}</div>
                                    @if($event->context)
                                        <pre class="small bg-body-secondary p-2 rounded mt-1 mb-0">{{ json_encode($event->context, JSON_PRETTY_PRINT) }}</pre>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const statusUrl = @json(route('orders.status', $order));
        const timeline = document.getElementById('timeline');
        const icon = (level) => ({success: '✔', error: '✖', warning: '⚠', info: 'ℹ'}[level] || 'ℹ');

        function renderEvents(events) {
            timeline.innerHTML = events.map(e => {
                const ctx = e.context && Object.keys(e.context).length
                    ? '<pre class="small bg-body-secondary p-2 rounded mt-1 mb-0">' + JSON.stringify(e.context, null, 2) + '</pre>'
                    : '';
                const t = e.created_at ? e.created_at.split('T')[1]?.slice(0, 8) : '';
                return `<li class="mb-2 d-flex gap-2 border-bottom pb-2">
                    <span class="flex-shrink-0">${icon(e.level)}</span>
                    <div><strong>${e.step_key}</strong> <span class="text-muted small">${t}</span>
                    <div>${e.message}</div>${ctx}</div></li>`;
            }).join('');
        }

        async function poll() {
            try {
                const r = await fetch(statusUrl, {headers: {'Accept': 'application/json'}});
                const data = await r.json();
                document.getElementById('o-status').textContent = data.order.status;
                document.getElementById('o-step').textContent = data.order.current_step || '—';
                document.getElementById('o-retries').textContent = data.order.retry_count;
                renderEvents(data.events);
            } catch (e) { /* ignore */ }
        }
        setInterval(poll, 3000);
    </script>
@endpush
