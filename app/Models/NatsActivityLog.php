<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatsActivityLog extends Model
{
    protected $fillable = ['type', 'message', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public static function logTaskQueued(int $taskId, string $runType, ?int $delaySeconds = null): void
    {
        self::log('task_queued', "Task #{$taskId} queued" . ($runType === 'later' && $delaySeconds ? " (run in {$delaySeconds}s)" : ''), [
            'task_id' => $taskId,
            'run_type' => $runType,
            'delay_seconds' => $delaySeconds,
        ]);
    }

    public static function logTaskCompleted(int $taskId, string $result = ''): void
    {
        self::log('task_completed', "Task #{$taskId} completed", ['task_id' => $taskId, 'result' => $result]);
    }

    public static function logTaskFailed(int $taskId, string $error): void
    {
        self::log('task_failed', "Task #{$taskId} failed: {$error}", ['task_id' => $taskId, 'error' => $error]);
    }

    public static function logEmailSent(string $to, string $emailType, array $meta = []): void
    {
        $label = match ($emailType) {
            'test' => 'Test email',
            'delayed' => 'Delayed email',
            'task_completed' => 'Task completed email',
            default => 'Email',
        };
        self::log('email_sent', "{$label} sent to {$to}", array_merge(['to' => $to, 'email_type' => $emailType], $meta));
    }

    public static function log(string $type, string $message, array $meta = []): void
    {
        try {
            self::create([
                'type' => $type,
                'message' => $message,
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable) {
            // Never fail the caller if logging fails (e.g. table missing, DB error)
        }
    }
}
