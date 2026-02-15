@extends('layouts.app')

@section('title', 'Activity Feed')

@section('content')
<h1>Activity feed</h1>
<p class="activity-meta" style="margin-bottom: 1rem;">All NATS-related events: publishes, job dispatches, job completions, failures, request/reply.</p>

<div class="card">
    @if($activities->isEmpty())
        <p>No activity yet.</p>
    @else
        <ul class="activity-list">
            @foreach($activities as $a)
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
        <div class="pagination">
            {{ $activities->links() }}
        </div>
    @endif
</div>

<p><a href="{{ route('nats.dashboard') }}" class="btn btn-secondary">Back to dashboard</a></p>
@endsection
