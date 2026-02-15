@extends('layouts.app')

@section('title', 'JetStream Streams')

@section('content')
<h1>JetStream Streams</h1>

@if($error)
<div class="alert alert-error">{{ $error }}</div>
@endif

@if(empty($streams))
<div class="card">
    <p>No streams found, or JetStream is not available.</p>
</div>
@else
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Subjects</th>
                <th>Messages</th>
            </tr>
        </thead>
        <tbody>
            @foreach($streams as $stream)
            <tr>
                <td>{{ is_object($stream) && method_exists($stream, 'getName') ? $stream->getName() : (is_array($stream) ? ($stream['config']['name'] ?? '—') : '—') }}</td>
                <td>{{ is_object($stream) && method_exists($stream, 'getSubjects') ? implode(', ', $stream->getSubjects() ?? []) : (is_array($stream) ? implode(', ', $stream['config']['subjects'] ?? []) : '—') }}</td>
                <td>{{ is_object($stream) && method_exists($stream, 'getMessageCount') ? $stream->getMessageCount() : (is_array($stream) ? ($stream['state']['messages'] ?? '—') : '—') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<p><a href="{{ route('nats.dashboard') }}" class="btn btn-secondary">Back to dashboard</a></p>
@endsection
