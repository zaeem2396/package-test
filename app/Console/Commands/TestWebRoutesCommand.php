<?php

namespace App\Console\Commands;

use App\Jobs\DelayedTestJob;
use App\Jobs\FailingTestJob;
use App\Jobs\ProcessTestJob;
use Illuminate\Console\Command;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Runs the logic of each NATS web route and reports pass/fail.
 * Use: php artisan nats:test-routes
 * Requires NATS server (e.g. docker compose up -d nats).
 */
class TestWebRoutesCommand extends Command
{
    protected $signature = 'nats:test-routes';

    protected $description = 'Run each NATS web route and check response (mirrors routes/web.php)';

    public function handle(): int
    {
        $this->info('Testing NATS web routes (logic from routes/web.php)...');
        $this->newLine();

        $results = [];

        // GET /
        $results['GET /'] = $this->runRoute('/', function () {
            return view('welcome');
        }, null, 'view');

        // GET /nats/dispatch
        $results['GET /nats/dispatch'] = $this->runRoute('/nats/dispatch', function () {
            ProcessTestJob::dispatch('Hello from web', ['source' => 'route'])->onConnection('nats');
            return ['ok' => true, 'message' => 'Job dispatched to NATS'];
        }, ['ok' => true, 'message' => 'Job dispatched to NATS']);

        // GET /nats/dispatch-failing
        $results['GET /nats/dispatch-failing'] = $this->runRoute('/nats/dispatch-failing', function () {
            FailingTestJob::dispatch('Test failure')->onConnection('nats');
            return ['ok' => true, 'message' => 'Failing job dispatched'];
        }, ['ok' => true, 'message' => 'Failing job dispatched']);

        // GET /nats/dispatch-delayed
        $results['GET /nats/dispatch-delayed'] = $this->runRoute('/nats/dispatch-delayed', function () {
            DelayedTestJob::dispatch(now()->addMinutes(5)->toIso8601String())
                ->onConnection('nats')
                ->delay(now()->addSeconds(10));
            return ['ok' => true, 'message' => 'Delayed job dispatched'];
        }, ['ok' => true, 'message' => 'Delayed job dispatched']);

        // GET /nats/publish
        $results['GET /nats/publish'] = $this->runRoute('/nats/publish', function () {
            Nats::publish('test.subject', ['event' => 'hello', 'at' => now()->toIso8601String()]);
            return ['ok' => true, 'message' => 'Published to test.subject'];
        }, ['ok' => true, 'message' => 'Published to test.subject']);

        // GET /nats/jetstream-check
        $results['GET /nats/jetstream-check'] = $this->runRoute('/nats/jetstream-check', function () {
            $js = Nats::jetstream();
            return ['jetstream_available' => $js->isAvailable()];
        }, null, 'has jetstream_available key');

        // Summary
        $this->newLine();
        $this->table(
            ['Route', 'Status', 'Notes'],
            collect($results)->map(fn ($r, $route) => [$route, $r['ok'] ? 'OK' : 'FAIL', $r['note'] ?? ''])->values()->all()
        );

        $failed = collect($results)->where('ok', false)->count();
        if ($failed > 0) {
            $this->error("{$failed} route(s) failed.");
            return self::FAILURE;
        }

        $this->info('All routes OK.');
        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>|null  $expectedJson  Expected keys/values (partial match)
     * @param  string|null  $expectedNote  Human-readable expectation if not JSON
     */
    private function runRoute(string $name, callable $action, ?array $expectedJson = null, ?string $expectedNote = null): array
    {
        try {
            $result = $action();
            if ($expectedJson !== null) {
                $arr = is_array($result) ? $result : (json_decode(json_encode($result), true) ?? []);
                foreach ($expectedJson as $key => $value) {
                    if (! array_key_exists($key, $arr) || $arr[$key] !== $value) {
                        return [
                            'ok' => false,
                            'note' => "Expected {$key}= " . json_encode($value) . ', got ' . json_encode($arr[$key] ?? 'missing'),
                        ];
                    }
                }
            }
            return ['ok' => true, 'note' => $expectedNote ?? 'OK'];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'note' => $e->getMessage(),
            ];
        }
    }
}
