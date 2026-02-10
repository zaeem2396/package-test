<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PocDemoLog extends Model
{
    protected $fillable = ['scenario', 'subject_or_connection', 'payload', 'success', 'message'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'success' => 'boolean',
        ];
    }

    public static function log(string $scenario, ?string $subjectOrConnection, mixed $payload, bool $success, ?string $message = null): self
    {
        return self::create([
            'scenario' => $scenario,
            'subject_or_connection' => $subjectOrConnection,
            'payload' => is_array($payload) ? $payload : ['value' => $payload],
            'success' => $success,
            'message' => $message,
        ]);
    }
}
