<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'reference',
        'customer_email',
        'amount',
        'status',
        'payment_response',
        'shipped_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_response' => 'array',
        'shipped_at' => 'datetime',
    ];

    public function markPaid(array $paymentResponse): void
    {
        $this->update([
            'status' => 'paid',
            'payment_response' => $paymentResponse,
        ]);
    }

    public function markShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }
}
