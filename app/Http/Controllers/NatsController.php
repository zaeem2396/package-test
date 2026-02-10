<?php

namespace App\Http\Controllers;

use App\Jobs\DelayedTaskJob;
use App\Jobs\FailingTestJob;
use App\Jobs\ProcessTaskJob;
use App\Jobs\SendDelayedEmailJob;
use App\Mail\TestEmailMail;
use App\Models\BroadcastLog;
use App\Models\NatsActivityLog;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LaravelNats\Laravel\Facades\Nats;
use Illuminate\Support\Facades\Mail;

class NatsController extends Controller
{
    public function dashboard(): View
    {
        $tasks = Task::latest()->take(50)->get();

        return view('nats.dashboard', compact('tasks'));
    }

    public function taskCreate(): View
    {
        return view('nats.task-create');
    }

    public function taskStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'run_type' => ['required', 'in:now,later'],
            'delay_seconds' => ['required_if:run_type,later', 'nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        $delaySeconds = $validated['run_type'] === 'later' ? (int) ($validated['delay_seconds'] ?? 5) : 0;

        $task = Task::create([
            'message' => $validated['message'],
            'status' => 'pending',
            'scheduled_for' => $delaySeconds > 0 ? now()->addSeconds($delaySeconds) : null,
        ]);

        try {
            if ($delaySeconds > 0) {
                DelayedTaskJob::dispatch($task->id)
                    ->onConnection('nats')
                    ->delay(now()->addSeconds($delaySeconds));
                $msg = "Task #{$task->id} scheduled to run in {$delaySeconds} seconds.";
            } else {
                ProcessTaskJob::dispatch($task->id)->onConnection('nats');
                $msg = "Task #{$task->id} queued. A worker will process it shortly.";
            }

            NatsActivityLog::logTaskQueued($task->id, $validated['run_type'], $delaySeconds > 0 ? $delaySeconds : null);
            return redirect()->route('dashboard')->with('success', $msg);
        } catch (\Throwable $e) {
            $task->update(['status' => 'failed', 'result' => $e->getMessage()]);

            return redirect()->route('tasks.create')
                ->with('error', $e->getMessage())
                ->with('error_detail', $this->formatExceptionDetail($e));
        }
    }

    public function publishForm(): View
    {
        $broadcastLogs = BroadcastLog::latest('received_at')->take(20)->get();
        $connections = array_keys(config('nats.connections', ['default' => []]));

        return view('nats.publish', compact('broadcastLogs', 'connections'));
    }

    public function publishStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'payload' => ['nullable', 'string', 'max:2000'],
            'connection' => ['nullable', 'string', 'max:64'],
        ]);

        $payload = ['message' => $validated['payload'] ?: 'No message', 'at' => now()->toIso8601String()];
        if ($validated['payload'] && json_decode($validated['payload']) !== null) {
            $payload = array_merge($payload, json_decode($validated['payload'], true) ?? []);
        }

        try {
            $conn = ! empty($validated['connection']) ? Nats::connection($validated['connection']) : Nats::connection();
            $conn->publish($validated['subject'], $payload);

            return redirect()->route('nats.publish')->with('success', "Published to subject: {$validated['subject']}");
        } catch (\Throwable $e) {
            return redirect()->route('nats.publish')
                ->with('error', $e->getMessage())
                ->with('error_detail', $this->formatExceptionDetail($e));
        }
    }

    public function ping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'connection' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $conn = ! empty($validated['connection']) ? Nats::connection($validated['connection']) : Nats::connection();
            $response = $conn->request('demo.ping', [], 2.0);
            $body = $response->getDecodedPayload();

            return redirect()->route('nats.publish')->with('success', 'Ping OK: ' . json_encode($body));
        } catch (\Throwable $e) {
            return redirect()->route('nats.publish')
                ->with('error', 'Ping failed: ' . $e->getMessage())
                ->with('error_detail', $this->formatExceptionDetail($e));
        }
    }

    public function status(): View
    {
        $connected = false;
        $jetstreamAvailable = false;
        $error = null;
        $errorDetail = null;

        try {
            Nats::connection();
            $connected = true;
            $js = Nats::jetstream();
            $jetstreamAvailable = $js->isAvailable();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $errorDetail = $this->formatExceptionDetail($e);
        }

        return view('nats.status', [
            'connected' => $connected,
            'jetstreamAvailable' => $jetstreamAvailable,
            'error' => $error,
            'errorDetail' => $errorDetail,
        ]);
    }

    public function failingStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            FailingTestJob::dispatch($validated['reason'] ?? 'Intentional failure')->onConnection('nats');

            return redirect()->back()->with('success', 'Failing job queued. It will appear in failed_jobs after max attempts.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->with('error_detail', $this->formatExceptionDetail($e));
        }
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            Mail::to($validated['email'])->send(new TestEmailMail(
                $validated['message'] ?? 'This is a test email from the NATS Tasks app.'
            ));

            NatsActivityLog::logEmailSent($validated['email'], 'test');
            return redirect()->back()->with('success', 'Test email sent to ' . $validated['email'] . '. Check Mailhog at http://localhost:8025 if using Docker.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->with('error_detail', $this->formatExceptionDetail($e));
        }
    }

    public function delayedEmailForm(): View
    {
        return view('nats.delayed-email');
    }

    public function delayedEmailStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:500'],
            'delay_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
        ]);

        $delaySeconds = (int) $validated['delay_seconds'];
        $body = $validated['message'] ?? 'Delayed test email from the NATS Tasks app (sent after ' . $delaySeconds . ' seconds).';

        try {
            // NATS driver does not support ->delay(); we pass delay to the job and it sleeps before sending
            SendDelayedEmailJob::dispatch($validated['email'], $body, $delaySeconds)
                ->onConnection('nats');

            return redirect()->route('email.delayed')->with(
                'success',
                "Email to {$validated['email']} will be sent in {$delaySeconds} seconds via NATS. Run the queue worker to process it. Check Mailhog at http://localhost:8025."
            );
        } catch (\Throwable $e) {
            return redirect()->route('email.delayed')
                ->with('error', $e->getMessage())
                ->with('error_detail', $this->formatExceptionDetail($e));
        }
    }

    public function logs(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $query = NatsActivityLog::query()->orderByDesc('created_at')->limit(200);

        if ($filter === 'tasks') {
            $query->whereIn('type', ['task_queued', 'task_completed', 'task_failed']);
        } elseif ($filter === 'emails') {
            $query->where('type', 'email_sent');
        }

        $entries = $query->get();

        return view('nats.logs', [
            'entries' => $entries,
            'filter' => $filter,
        ]);
    }

    private function formatExceptionDetail(\Throwable $e): array
    {
        return [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
    }
}
