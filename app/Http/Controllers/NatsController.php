<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOrderJob;
use App\Jobs\SendReminderJob;
use App\Jobs\FailingDemoJob;
use App\Models\NatsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelNats\Laravel\Facades\Nats;

class NatsController extends Controller
{
    /**
     * NATS dashboard – overview and links to all features.
     */
    public function dashboard()
    {
        $jetstreamAvailable = false;
        $accountInfo = null;
        try {
            $js = Nats::jetstream();
            $jetstreamAvailable = $js->isAvailable();
            if ($jetstreamAvailable) {
                $accountInfo = $js->getAccountInfo();
            }
        } catch (\Throwable) {
            // NATS or JetStream not available
        }

        try {
            $recentActivities = NatsActivity::orderByDesc('created_at')->limit(30)->get();
        } catch (\Throwable $e) {
            $recentActivities = collect();
        }

        return view('nats.dashboard', [
            'jetstreamAvailable' => $jetstreamAvailable,
            'accountInfo' => $accountInfo,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Publish a message to a subject (demo).
     */
    public function publish(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'payload' => 'nullable|string',
        ]);

        $subject = $request->input('subject');
        $payload = $request->input('payload', '{}');
        $decoded = json_decode($payload, true);
        $data = is_array($decoded) ? $decoded : ['message' => $payload];

        Nats::publish($subject, $data);

        try {
            NatsActivity::log('published', "Published to subject: {$subject}", [
                'subject' => $subject,
                'payload' => $data,
            ]);
        } catch (\Throwable $e) {
            // Table may not exist in tests or before migration
        }

        return back()->with('success', "Published to subject: {$subject}");
    }

    /**
     * Request/Reply demo – send request and show reply.
     */
    public function requestReply(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'payload' => 'nullable|string',
        ]);

        $subject = $request->input('subject');
        $payload = $request->input('payload', '{}');
        $decoded = json_decode($payload, true);
        $data = is_array($decoded) ? $decoded : ['message' => $payload];

        try {
            $reply = Nats::request($subject, $data, timeout: 5.0);
            $body = $reply->getDecodedPayload();
            try {
                NatsActivity::log('request_reply', "Request/Reply to {$subject} – reply received", [
                    'subject' => $subject,
                    'request' => $data,
                    'reply' => $body,
                ]);
            } catch (\Throwable $_) {}
            return back()->with('success', 'Reply received')->with('reply', $body);
        } catch (\Throwable $e) {
            try {
                NatsActivity::log('request_reply', "Request/Reply to {$subject} – failed: " . $e->getMessage(), [
                    'subject' => $subject,
                    'request' => $data,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable $_) {}
            return back()->with('error', 'Request failed: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch a job to the NATS queue.
     */
    public function dispatchJob(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string|max:64',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $orderId = $request->input('order_id');
        $amount = (float) $request->input('amount', 0);

        ProcessOrderJob::dispatch($orderId, $amount)->onConnection('nats');

        try {
            NatsActivity::log('job_dispatched', "ProcessOrderJob dispatched for order: {$orderId} (amount: {$amount})", [
                'job' => ProcessOrderJob::class,
                'order_id' => $orderId,
                'amount' => $amount,
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', "Job dispatched for order: {$orderId}");
    }

    /**
     * Schedule a delayed job (JetStream).
     */
    public function dispatchDelayed(Request $request)
    {
        $request->validate([
            'delay_seconds' => 'required|integer|min:1|max:3600',
            'message' => 'nullable|string|max:500',
        ]);

        $delaySeconds = (int) $request->input('delay_seconds');
        $message = $request->input('message', 'Reminder');

        $runAt = now()->addSeconds($delaySeconds);
        SendReminderJob::dispatch($message)->onConnection('nats')->delay($runAt);

        try {
            NatsActivity::log('delayed_scheduled', "SendReminderJob scheduled in {$delaySeconds}s: \"{$message}\"", [
                'job' => SendReminderJob::class,
                'delay_seconds' => $delaySeconds,
                'message' => $message,
                'run_at' => $runAt->toIso8601String(),
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', "Delayed job scheduled in {$delaySeconds} seconds.");
    }

    /**
     * Dispatch a job that will fail (for DLQ demo).
     */
    public function dispatchFailing(Request $request)
    {
        FailingDemoJob::dispatch()->onConnection('nats');

        try {
            NatsActivity::log('job_dispatched', 'FailingDemoJob dispatched (will fail and land in failed_jobs/DLQ)', [
                'job' => FailingDemoJob::class,
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', 'Failing job dispatched. Run the queue worker to see it fail and land in DLQ/failed_jobs.');
    }

    /**
     * List JetStream streams (Artisan or API).
     */
    public function streams()
    {
        $streams = [];
        $error = null;
        try {
            $js = Nats::jetstream();
            if (!$js->isAvailable()) {
                $error = 'JetStream is not available.';
            } else {
                $result = $js->listStreams(offset: 0);
                $streams = $result['streams'] ?? [];
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('nats.streams', ['streams' => $streams, 'error' => $error]);
    }

    /**
     * List failed jobs from the database.
     */
    public function failedJobs()
    {
        try {
            $jobs = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(50)
                ->get();
        } catch (\Throwable $e) {
            $jobs = collect();
        }

        return view('nats.failed-jobs', ['jobs' => $jobs]);
    }

    /**
     * Full activity feed (paginated).
     */
    public function activity()
    {
        try {
            $activities = NatsActivity::orderByDesc('created_at')->paginate(50);
        } catch (\Throwable $e) {
            $activities = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        }
        return view('nats.activity', ['activities' => $activities]);
    }
}
