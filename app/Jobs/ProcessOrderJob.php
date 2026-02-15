<?php

namespace App\Jobs;

use App\Models\NatsActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessOrderJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(
        public string $orderId,
        public float $amount = 0
    ) {}

    public function handle(): void
    {
        NatsActivity::log('job_processed', "ProcessOrderJob completed: order {$this->orderId}, amount {$this->amount}", [
            'job' => self::class,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
        ]);
    }
}
