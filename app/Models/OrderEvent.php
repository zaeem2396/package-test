<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $step_key
 * @property string $message
 * @property string $level
 * @property array<string, mixed>|null $context
 */
final class OrderEvent extends Model
{
    protected $fillable = [
        'order_id',
        'step_key',
        'message',
        'level',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
