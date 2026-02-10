<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task completed</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #059669;">Task #{{ $task->id }} completed</h1>
    <p><strong>Message:</strong> {{ $task->message }}</p>
    <p><strong>Processed at:</strong> {{ $task->processed_at?->toIso8601String() ?? '—' }}</p>
    <p><strong>Result:</strong> {{ $task->result ?? '—' }}</p>
    <p style="margin-top: 24px; font-size: 12px; color: #64748b;">
        From {{ config('app.name') }}.
    </p>
</body>
</html>
