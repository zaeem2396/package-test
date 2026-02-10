<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'message',
        'status',
        'scheduled_for',
        'processed_at',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
