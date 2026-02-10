<?php

namespace App\Http\Controllers;

use App\Jobs\AlwaysFailingDemoJob;
use App\Jobs\DelayedDemoJob;
use App\Jobs\RetryableDemoJob;
use App\Jobs\SimpleDispatchDemoJob;
use App\Models\PocDemoLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use LaravelNats\Core\JetStream\ConsumerConfig;
use LaravelNats\Core\JetStream\JetStreamConsumedMessage;
use LaravelNats\Core\JetStream\StreamConfig;
use LaravelNats\Laravel\Facades\Nats;

class NatsPocController extends Controller
{
    private const POC_CACHE_PUBSUB = 'poc_last_pubsub';
    private const POC_STREAM = 'POC_DEMO';
    private const POC_CONSUMER = 'poc_demo_consumer';

    public function index()
    {
        $recent = PocDemoLog::orderByDesc('created_at')->limit(20)->get();
        return view('nats-poc.index', ['recent' => $recent]);
    }

    // ---- Pub/Sub ----
    public function pubSub()
    {
        $last = Cache::get(self::POC_CACHE_PUBSUB);
        $logs = PocDemoLog::where('scenario', 'pubsub')->orderByDesc('created_at')->limit(10)->get();
        return view('nats-poc.pubsub', ['last' => $last, 'logs' => $logs]);
    }

    public function pubSubPublish(Request $request)
    {
        $subject = $request->input('subject', 'poc.demo.events');
        $payload = $request->input('payload', ['message' => 'Hello from PoC at ' . now()->toIso8601String()]);
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: ['raw' => $payload];
        }
        try {
            Nats::publish($subject, $payload);
            PocDemoLog::log('pubsub', $subject, $payload, true, 'Published successfully.');
            return redirect()->route('nats-poc.pubsub')->with('success', "Published to {$subject}");
        } catch (\Throwable $e) {
            PocDemoLog::log('pubsub', $subject, $payload, false, $e->getMessage());
            return redirect()->route('nats-poc.pubsub')->with('error', $e->getMessage());
        }
    }

    public function pubSubLast()
    {
        $last = Cache::get(self::POC_CACHE_PUBSUB);
        return response()->json($last ?? ['message' => 'No message received yet. Run: php artisan poc:subscriber']);
    }

    // ---- Request/Reply ----
    public function requestReply()
    {
        $logs = PocDemoLog::whereIn('scenario', ['request_reply_pass', 'request_reply_fail'])->orderByDesc('created_at')->limit(10)->get();
        return view('nats-poc.request-reply', ['logs' => $logs]);
    }

    public function requestReplySend(Request $request)
    {
        $subject = $request->input('subject', 'poc.demo.request');
        $payload = $request->input('payload', ['ping' => true, 'at' => now()->toIso8601String()]);
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: ['raw' => $payload];
        }
        $timeout = (float) $request->input('timeout', 3);
        try {
            $reply = Nats::request($subject, $payload, timeout: $timeout);
            $decoded = $reply->getDecodedPayload();
            PocDemoLog::log('request_reply_pass', $subject, $decoded, true, 'Reply received.');
            return redirect()->route('nats-poc.request-reply')->with('success', 'Reply: ' . json_encode($decoded, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            PocDemoLog::log('request_reply_fail', $subject, ['error' => $e->getMessage()], false, $e->getMessage());
            return redirect()->route('nats-poc.request-reply')->with('error', $e->getMessage());
        }
    }

    // ---- Multiple connections ----
    public function multipleConnections()
    {
        $logs = PocDemoLog::where('scenario', 'multi_connection')->orderByDesc('created_at')->limit(10)->get();
        return view('nats-poc.multiple-connections', ['logs' => $logs]);
    }

    public function multipleConnectionsPublish(Request $request)
    {
        $connection = $request->input('connection', 'default');
        $subject = $request->input('subject', 'poc.multi.connection');
        $payload = ['connection' => $connection, 'at' => now()->toIso8601String()];
        try {
            Nats::connection($connection)->publish($subject, $payload);
            PocDemoLog::log('multi_connection', $connection, $payload, true, "Published via connection: {$connection}");
            return redirect()->route('nats-poc.multiple-connections')->with('success', "Published to {$subject} via {$connection}");
        } catch (\Throwable $e) {
            PocDemoLog::log('multi_connection', $connection, $payload, false, $e->getMessage());
            return redirect()->route('nats-poc.multiple-connections')->with('error', $e->getMessage());
        }
    }

    // ---- Queue ----
    public function queue()
    {
        $logs = PocDemoLog::where('scenario', 'like', 'queue_%')->orderByDesc('created_at')->limit(30)->get();
        $failedCount = DB::table('failed_jobs')->count();
        return view('nats-poc.queue', ['logs' => $logs, 'failedCount' => $failedCount]);
    }

    public function queueDispatch(Request $request)
    {
        $message = $request->input('message', 'PoC dispatch at ' . now()->toIso8601String());
        SimpleDispatchDemoJob::dispatch($message)->onConnection('nats');
        PocDemoLog::log('queue_dispatch', 'nats', ['message' => $message], true, 'Job dispatched. Run worker to process.');
        return redirect()->route('nats-poc.queue')->with('success', 'Job dispatched. Ensure worker is running: php artisan queue:work nats');
    }

    public function queueRetry(Request $request)
    {
        $id = $request->input('id', 'retry-' . uniqid());
        RetryableDemoJob::dispatch($id)->onConnection('nats');
        PocDemoLog::log('queue_retry', 'nats', ['job_id' => $id], true, 'Retryable job dispatched (fails 3x then succeeds).');
        return redirect()->route('nats-poc.queue')->with('success', 'Retryable job dispatched. Worker will retry with backoff.');
    }

    public function queueFailed(Request $request)
    {
        $reason = $request->input('reason', 'PoC failed job + failed() callback demo');
        AlwaysFailingDemoJob::dispatch($reason)->onConnection('nats');
        PocDemoLog::log('queue_failed', 'nats', ['reason' => $reason], false, 'Failing job dispatched. Will appear in failed_jobs.');
        return redirect()->route('nats-poc.queue')->with('success', 'Failing job dispatched. After retries it will be in failed_jobs and DLQ (if configured).');
    }

    public function queueDelayed(Request $request)
    {
        $delaySeconds = max(5, min(120, (int) $request->input('delay_seconds', 10)));
        $scheduledFor = now()->addSeconds($delaySeconds)->toIso8601String();
        try {
            Queue::connection('nats')->later($delaySeconds, new DelayedDemoJob($scheduledFor, $delaySeconds));
            PocDemoLog::log('queue_delayed', 'nats', ['delay_seconds' => $delaySeconds, 'scheduled_for' => $scheduledFor], true, "Delayed job scheduled for {$delaySeconds}s.");
            return redirect()->route('nats-poc.queue')->with('success', "Delayed job scheduled for {$delaySeconds}s. Ensure delayed is enabled and worker running.");
        } catch (\Throwable $e) {
            PocDemoLog::log('queue_delayed', 'nats', [], false, $e->getMessage());
            return redirect()->route('nats-poc.queue')->with('error', $e->getMessage());
        }
    }

    // ---- JetStream ----
    public function jetstream()
    {
        $js = Nats::jetstream();
        $available = $js->isAvailable();
        $accountInfo = null;
        $streams = [];
        if ($available) {
            try {
                $accountInfo = $js->getAccountInfo();
            } catch (\Throwable) {
            }
            try {
                $r = $js->listStreams(offset: 0);
                $streams = $r['streams'] ?? [];
            } catch (\Throwable) {
            }
        }
        $logs = PocDemoLog::where('scenario', 'like', 'js_%')->orderByDesc('created_at')->limit(15)->get();
        return view('nats-poc.jetstream', [
            'available' => $available,
            'accountInfo' => $accountInfo,
            'streams' => $streams,
            'logs' => $logs,
        ]);
    }

    public function jetstreamStreamInfo(Request $request)
    {
        $name = $request->input('stream', self::POC_STREAM);
        try {
            $js = Nats::jetstream();
            $info = $js->getStreamInfo($name);
            $payload = [
                'name' => $info->getConfig()->getName(),
                'messages' => $info->getMessageCount(),
                'bytes' => $info->getByteCount(),
            ];
            PocDemoLog::log('js_stream_info', $name, $payload, true, 'Stream info retrieved.');
            return redirect()->route('nats-poc.jetstream')->with('success', json_encode($payload, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            PocDemoLog::log('js_stream_info', $name, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    public function jetstreamCreateStream(Request $request)
    {
        $name = $request->input('stream', self::POC_STREAM);
        $subjects = $request->input('subjects', 'poc.demo.>');
        $subjectsArray = is_string($subjects) ? array_map('trim', explode(',', $subjects)) : $subjects;
        try {
            $js = Nats::jetstream();
            $config = (new StreamConfig($name, $subjectsArray))
                ->withDescription('PoC demo stream')
                ->withMaxMessages(10000)
                ->withStorage(StreamConfig::STORAGE_FILE);
            $info = $js->createStream($config);
            PocDemoLog::log('js_create_stream', $name, ['subjects' => $subjectsArray], true, 'Stream created.');
            return redirect()->route('nats-poc.jetstream')->with('success', "Stream {$name} created.");
        } catch (\Throwable $e) {
            PocDemoLog::log('js_create_stream', $name, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    public function jetstreamPublish(Request $request)
    {
        $subject = $request->input('subject', 'poc.demo.events');
        $payload = ['event' => 'poc', 'at' => now()->toIso8601String()];
        try {
            Nats::publish($subject, $payload);
            PocDemoLog::log('js_publish', $subject, $payload, true, 'Published (stream captures if subject matches).');
            return redirect()->route('nats-poc.jetstream')->with('success', "Published to {$subject}");
        } catch (\Throwable $e) {
            PocDemoLog::log('js_publish', $subject, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    public function jetstreamGetMessage(Request $request)
    {
        $stream = $request->input('stream', self::POC_STREAM);
        $seq = (int) $request->input('sequence', 1);
        try {
            $js = Nats::jetstream();
            $message = $js->getMessage($stream, $seq);
            PocDemoLog::log('js_get_message', $stream, $message, true, "Message {$seq} retrieved.");
            return redirect()->route('nats-poc.jetstream')->with('success', json_encode($message, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            PocDemoLog::log('js_get_message', $stream, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    public function jetstreamPurge(Request $request)
    {
        $stream = $request->input('stream', self::POC_STREAM);
        try {
            $js = Nats::jetstream();
            $js->purgeStream($stream);
            PocDemoLog::log('js_purge', $stream, [], true, 'Stream purged.');
            return redirect()->route('nats-poc.jetstream')->with('success', "Stream {$stream} purged.");
        } catch (\Throwable $e) {
            PocDemoLog::log('js_purge', $stream, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    public function jetstreamCreateConsumer(Request $request)
    {
        $stream = $request->input('stream', self::POC_STREAM);
        $consumer = $request->input('consumer', self::POC_CONSUMER);
        try {
            $js = Nats::jetstream();
            $config = (new ConsumerConfig($consumer))
                ->withFilterSubject('poc.demo.>')
                ->withDeliverPolicy(ConsumerConfig::DELIVER_ALL)
                ->withAckPolicy(ConsumerConfig::ACK_EXPLICIT);
            $js->createConsumer($stream, $consumer, $config);
            PocDemoLog::log('js_create_consumer', "{$stream}/{$consumer}", [], true, 'Consumer created.');
            return redirect()->route('nats-poc.jetstream')->with('success', "Consumer {$consumer} created on {$stream}");
        } catch (\Throwable $e) {
            PocDemoLog::log('js_create_consumer', $stream, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    public function jetstreamFetchAck(Request $request)
    {
        $stream = $request->input('stream', self::POC_STREAM);
        $consumer = $request->input('consumer', self::POC_CONSUMER);
        $action = $request->input('action', 'ack');
        try {
            $js = Nats::jetstream();
            $msg = $js->fetchNextMessage($stream, $consumer, timeout: 2.0, noWait: false);
            if ($msg instanceof JetStreamConsumedMessage) {
                $payload = $msg->getPayload();
                if ($action === 'ack') {
                    $js->ack($msg);
                } elseif ($action === 'nak') {
                    $js->nak($msg);
                } else {
                    $js->term($msg);
                }
                PocDemoLog::log('js_fetch_ack', "{$stream}/{$consumer}", ['action' => $action, 'payload' => $payload], true, "Fetched and {$action}.");
                return redirect()->route('nats-poc.jetstream')->with('success', "Fetched message, {$action}: " . json_encode($payload));
            }
            PocDemoLog::log('js_fetch_ack', "{$stream}/{$consumer}", [], true, 'No message available.');
            return redirect()->route('nats-poc.jetstream')->with('success', 'No message in stream (or already consumed).');
        } catch (\Throwable $e) {
            PocDemoLog::log('js_fetch_ack', $stream, [], false, $e->getMessage());
            return redirect()->route('nats-poc.jetstream')->with('error', $e->getMessage());
        }
    }

    // ---- Failure scenarios ----
    public function failures()
    {
        $logs = PocDemoLog::where('success', false)->orWhereIn('scenario', ['request_reply_fail'])->orderByDesc('created_at')->limit(20)->get();
        return view('nats-poc.failures', ['logs' => $logs]);
    }

    public function failureRequestTimeout(Request $request)
    {
        $subject = 'poc.nonexistent.responder';
        $timeout = 2.0;
        try {
            Nats::request($subject, ['test' => true], timeout: $timeout);
            return redirect()->route('nats-poc.failures')->with('success', 'Unexpected: got reply.');
        } catch (\Throwable $e) {
            PocDemoLog::log('failure_request_timeout', $subject, ['error' => $e->getMessage()], false, $e->getMessage());
            return redirect()->route('nats-poc.failures')->with('success', 'Expected failure: ' . $e->getMessage());
        }
    }
}
