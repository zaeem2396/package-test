<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $amount
 * @property string $email
 * @property string $status
 * @property string|null $current_step
 * @property int $retry_count
 * @property string|null $workflow_id
 */
final class Order extends Model
{
    protected $fillable = [
        'amount',
        'email',
        'status',
        'current_step',
        'retry_count',
        'workflow_id',
    ];

    protected function casts(): array
    {
        return [
            'retry_count' => 'integer',
        ];
    }

    /** @return HasMany<OrderEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }
}
