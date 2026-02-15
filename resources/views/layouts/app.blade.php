<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NATS Demo') – {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Instrument Sans', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; line-height: 1.6; }
        .container { max-width: 56rem; margin: 0 auto; padding: 1.5rem; }
        nav { background: #1e293b; padding: 0.75rem 1rem; margin-bottom: 1.5rem; border-radius: 0.5rem; }
        nav a { color: #94a3b8; text-decoration: none; margin-right: 1rem; }
        nav a:hover { color: #f8fafc; }
        nav a.active { color: #38bdf8; }
        h1 { font-size: 1.75rem; margin-bottom: 1rem; color: #f8fafc; }
        h2 { font-size: 1.25rem; margin: 1.25rem 0 0.5rem; color: #cbd5e1; }
        .card { background: #1e293b; border-radius: 0.5rem; padding: 1.25rem; margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.25rem; color: #94a3b8; font-size: 0.875rem; }
        input[type=text], input[type=number], textarea { width: 100%; padding: 0.5rem 0.75rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.375rem; color: #e2e8f0; margin-bottom: 0.75rem; }
        textarea { min-height: 80px; resize: vertical; }
        button, .btn { display: inline-block; padding: 0.5rem 1rem; background: #38bdf8; color: #0f172a; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; text-decoration: none; font-size: 0.875rem; }
        button:hover, .btn:hover { background: #7dd3fc; }
        .btn-secondary { background: #475569; color: #e2e8f0; }
        .btn-secondary:hover { background: #64748b; }
        .alert { padding: 0.75rem 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
        .alert-success { background: #14532d; color: #86efac; }
        .alert-error { background: #7f1d1d; color: #fca5a5; }
        ul.links { list-style: none; }
        ul.links li { margin-bottom: 0.5rem; }
        ul.links a { color: #38bdf8; text-decoration: none; }
        ul.links a:hover { text-decoration: underline; }
        pre { background: #0f172a; padding: 1rem; border-radius: 0.375rem; overflow-x: auto; font-size: 0.8125rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #334155; }
        th { color: #94a3b8; font-weight: 500; }
        .activity-list { list-style: none; }
        .activity-item { padding: 0.75rem 1rem; border-bottom: 1px solid #334155; display: flex; gap: 0.75rem; align-items: flex-start; }
        .activity-item:last-child { border-bottom: none; }
        .activity-type { flex-shrink: 0; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; text-transform: uppercase; font-weight: 600; }
        .activity-type.published { background: #1e3a5f; color: #7dd3fc; }
        .activity-type.job_dispatched { background: #1e293b; color: #94a3b8; }
        .activity-type.job_processed { background: #14532d; color: #86efac; }
        .activity-type.job_failed { background: #7f1d1d; color: #fca5a5; }
        .activity-type.request_reply { background: #422006; color: #fcd34d; }
        .activity-type.delayed_scheduled { background: #312e81; color: #c4b5fd; }
        .activity-summary { flex: 1; word-break: break-word; }
        .activity-meta { font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }
        .activity-payload { margin-top: 0.5rem; font-size: 0.8125rem; }
        .pagination { margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 0.35rem 0.6rem; background: #334155; border-radius: 0.25rem; color: #e2e8f0; text-decoration: none; }
        .pagination a:hover { background: #475569; }
        .pagination .current { background: #38bdf8; color: #0f172a; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="{{ url('/') }}">Home</a>
            <a href="{{ route('nats.dashboard') }}" class="{{ request()->routeIs('nats.dashboard') ? 'active' : '' }}">NATS Dashboard</a>
            <a href="{{ route('nats.streams') }}">JetStream Streams</a>
            <a href="{{ route('nats.failed-jobs') }}">Failed Jobs</a>
            <a href="{{ route('nats.activity') }}" class="{{ request()->routeIs('nats.activity') ? 'active' : '' }}">Activity</a>
        </nav>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</body>
</html>
