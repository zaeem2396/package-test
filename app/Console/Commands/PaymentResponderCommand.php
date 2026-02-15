<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Subscribes to payments.validate and replies with approval (Request/Reply pattern).
 * Run: php artisan nats:payment-responder
 */
class PaymentResponderCommand extends Command
{
    protected $signature = 'nats:payment-responder';

    protected $description = 'Subscribe to payments.validate and reply with approval (RPC responder)';

    public function handle(): int
    {
        $this->info('Listening for payments.validate requests... (Ctrl+C to stop)');

        Nats::subscribe('payments.validate', function ($message) {
            $payload = $message->getDecodedPayload();
            $replyTo = $message->getReplyTo();
            if (!$replyTo) {
                return;
            }
            $orderId = $payload['order_id'] ?? null;
            $amount = (float) ($payload['amount'] ?? 0);
            // PoC: approve all under 10000, reject above
            $approved = $amount > 0 && $amount < 10000;
            $response = [
                'approved' => $approved,
                'order_id' => $orderId,
                'transaction_id' => 'txn_' . bin2hex(random_bytes(8)),
                'at' => now()->toIso8601String(),
            ];
            Nats::publish($replyTo, $response);
        });

        while (true) {
            Nats::process(1.0);
        }

        return self::SUCCESS; // unreachable; loop runs until Ctrl+C
    }
}
