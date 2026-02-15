<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatsActivity extends Model
{
    protected $table = 'nats_activities';

    protected $fillable = ['type', 'summary', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public static function log(string $type, string $summary, array $payload = []): self
    {
        return self::create([
            'type' => $type,
            'summary' => $summary,
            'payload' => $payload,
        ]);
    }
}
