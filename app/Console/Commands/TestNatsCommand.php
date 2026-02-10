<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTestJob;
use Illuminate\Console\Command;
use LaravelNats\Laravel\Facades\Nats;

class TestNatsCommand extends Command
{
    protected $signature = 'nats:test
                            {--scenario=all : Scenario to run: connection, publish, subscribe, queue-dispatch, jetstream, all}';

    protected $description = 'Run NATS package test scenarios (requires NATS server)';

    public function handle(): int
    {
        $scenario = $this->option('scenario');

        $this->info('NATS package test - scenario: ' . $scenario);

        try {
            match ($scenario) {
                'connection' => $this->testConnection(),
                'publish' => $this->testPublish(),
                'subscribe' => $this->testSubscribe(),
                'queue-dispatch' => $this->testQueueDispatch(),
                'jetstream' => $this->testJetStream(),
                'all' => $this->runAll(),
                default => $this->error('Unknown scenario: ' . $scenario),
            };
        } catch (\Throwable $e) {
            $this->error('Scenario failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testConnection(): void
    {
        Nats::connection(); // connect is done inside connection()
        $this->info('Connection: OK');
    }

    private function testPublish(): void
    {
        Nats::publish('test.command', ['source' => 'nats:test', 'at' => now()->toIso8601String()]);
        $this->info('Publish: OK');
    }

    private function testSubscribe(): void
    {
        $received = null;
        $sid = Nats::subscribe('test.command', function ($message) use (&$received) {
            $received = $message->getDecodedPayload();
        });
        Nats::publish('test.command', ['ping' => true]);
        $count = Nats::process(1.0);
        Nats::unsubscribe($sid);
        $this->info('Subscribe: OK (processed ' . $count . ' messages)');
        if ($received) {
            $this->line('  Received: ' . json_encode($received));
        }
    }

    private function testQueueDispatch(): void
    {
        ProcessTestJob::dispatch('From nats:test', ['scenario' => 'queue-dispatch'])->onConnection('nats');
        $this->info('Queue dispatch: OK (run queue:work nats to process)');
    }

    private function testJetStream(): void
    {
        $js = Nats::jetstream();
        if (! $js->isAvailable()) {
            $this->warn('JetStream: not available (start NATS with --jetstream)');

            return;
        }
        $this->info('JetStream: available');
    }

    private function runAll(): void
    {
        $this->testConnection();
        $this->testPublish();
        $this->testSubscribe();
        $this->testQueueDispatch();
        $this->testJetStream();
        $this->info('All scenarios completed.');
    }
}
