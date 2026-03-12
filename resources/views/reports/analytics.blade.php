@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
<h1 class="text-2xl font-bold mb-6">Analytics Report</h1>
<p class="text-gray-500 mb-4">This page intentionally loads slowly (heavy queries + delay) for demo purposes.</p>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Total Users</p>
        <p class="text-2xl font-semibold">{{ $userCount }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Total Activities</p>
        <p class="text-2xl font-semibold">{{ $activityCount }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Projects</p>
        <p class="text-2xl font-semibold">{{ $projectStats->count() }}</p>
    </div>
</div>
<div class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Tasks by status</h2>
    <ul class="space-y-1">
        @foreach($statusCounts as $status => $total)
            <li>{{ $status }}: {{ $total }}</li>
        @endforeach
    </ul>
</div>
<div>
    <h2 class="text-lg font-semibold mb-2">Tasks per project</h2>
    <ul class="space-y-2">
        @foreach($projectStats as $p)
            <li class="flex justify-between"><span>{{ $p['name'] }}</span><span>{{ $p['tasks_count'] }}</span></li>
        @endforeach
    </ul>
</div>
@endsection
