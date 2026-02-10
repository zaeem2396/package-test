<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #1e40af;">NATS Tasks — Test Email</h1>
    <p>{{ $body }}</p>
    <p style="margin-top: 24px; font-size: 12px; color: #64748b;">
        Sent at {{ now()->toIso8601String() }} from {{ config('app.name') }}.
    </p>
</body>
</html>
