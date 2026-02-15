@extends('layouts.app')

@section('title', 'Failed Jobs')

@section('content')
<h1>Failed Jobs</h1>

<div class="card">
    @if($jobs->isEmpty())
        <p>No failed jobs.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Queue</th>
                    <th>Failed at</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jobs as $job)
                <tr>
                    <td>{{ $job->uuid ?? $job->id }}</td>
                    <td>{{ $job->queue }}</td>
                    <td>{{ $job->failed_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<p><a href="{{ route('nats.dashboard') }}" class="btn btn-secondary">Back to dashboard</a></p>
@endsection
