<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\NatsController;
use App\Http\Controllers\NatsPocController;
use App\Http\Controllers\RuntimeInsightTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
Route::get('/welcome', fn () => view('welcome'))->name('welcome');

Route::get('/dashboard', [NatsController::class, 'dashboard'])->name('dashboard');

// Chat PoC (real-time collaboration demo with NATS)
Route::prefix('chat')->name('chat.')->group(function (): void {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::get('/create', [ChatController::class, 'create'])->name('create');
    Route::post('/rooms', [ChatController::class, 'storeRoom'])->name('room.store');
    Route::get('/room/{room}', [ChatController::class, 'show'])->name('room.show');
    Route::get('/room/{room}/presence', [ChatController::class, 'presence'])->name('room.presence');
    Route::post('/messages', [ChatController::class, 'store'])->name('messages.store');
    Route::get('/room/{room}/messages', [ChatController::class, 'messages'])->name('room.messages');
});
Route::get('/nats/queue', fn () => redirect()->route('tasks.create'));
Route::get('/nats/delayed', fn () => redirect()->route('tasks.create'));

Route::get('/tasks/create', [NatsController::class, 'taskCreate'])->name('tasks.create');
Route::post('/tasks', [NatsController::class, 'taskStore'])->name('tasks.store');

Route::get('/broadcast', [NatsController::class, 'publishForm'])->name('nats.publish');
Route::post('/broadcast', [NatsController::class, 'publishStore'])->name('nats.publish.store');
Route::post('/broadcast/ping', [NatsController::class, 'ping'])->name('nats.ping');

Route::get('/status', [NatsController::class, 'status'])->name('nats.status');
Route::get('/logs', [NatsController::class, 'logs'])->name('logs');
Route::post('/send-test-email', [NatsController::class, 'sendTestEmail'])->name('send-test-email');

Route::get('/email/delayed', [NatsController::class, 'delayedEmailForm'])->name('email.delayed');
Route::post('/email/delayed', [NatsController::class, 'delayedEmailStore'])->name('email.delayed.store');

Route::post('/nats/failing', [NatsController::class, 'failingStore'])->name('nats.failing.store');

// NATS Package PoC — full feature demo (pass + fail scenarios)
Route::prefix('nats-poc')->name('nats-poc.')->group(function (): void {
    Route::get('/', [NatsPocController::class, 'index'])->name('index');
    Route::get('/pubsub', [NatsPocController::class, 'pubSub'])->name('pubsub');
    Route::post('/pubsub', [NatsPocController::class, 'pubSubPublish'])->name('pubsub.publish');
    Route::get('/pubsub/last', [NatsPocController::class, 'pubSubLast'])->name('pubsub.last');
    Route::get('/request-reply', [NatsPocController::class, 'requestReply'])->name('request-reply');
    Route::post('/request-reply', [NatsPocController::class, 'requestReplySend'])->name('request-reply.send');
    Route::get('/multiple-connections', [NatsPocController::class, 'multipleConnections'])->name('multiple-connections');
    Route::post('/multiple-connections', [NatsPocController::class, 'multipleConnectionsPublish'])->name('multiple-connections.publish');
    Route::get('/queue', [NatsPocController::class, 'queue'])->name('queue');
    Route::post('/queue/dispatch', [NatsPocController::class, 'queueDispatch'])->name('queue.dispatch');
    Route::post('/queue/retry', [NatsPocController::class, 'queueRetry'])->name('queue.retry');
    Route::post('/queue/failed', [NatsPocController::class, 'queueFailed'])->name('queue.failed');
    Route::post('/queue/delayed', [NatsPocController::class, 'queueDelayed'])->name('queue.delayed');
    Route::get('/jetstream', [NatsPocController::class, 'jetstream'])->name('jetstream');
    Route::post('/jetstream/stream-info', [NatsPocController::class, 'jetstreamStreamInfo'])->name('jetstream.stream-info');
    Route::post('/jetstream/create-stream', [NatsPocController::class, 'jetstreamCreateStream'])->name('jetstream.create-stream');
    Route::post('/jetstream/publish', [NatsPocController::class, 'jetstreamPublish'])->name('jetstream.publish');
    Route::post('/jetstream/get-message', [NatsPocController::class, 'jetstreamGetMessage'])->name('jetstream.get-message');
    Route::post('/jetstream/purge', [NatsPocController::class, 'jetstreamPurge'])->name('jetstream.purge');
    Route::post('/jetstream/create-consumer', [NatsPocController::class, 'jetstreamCreateConsumer'])->name('jetstream.create-consumer');
    Route::post('/jetstream/fetch-ack', [NatsPocController::class, 'jetstreamFetchAck'])->name('jetstream.fetch-ack');
    Route::get('/failures', [NatsPocController::class, 'failures'])->name('failures');
    Route::post('/failures/request-timeout', [NatsPocController::class, 'failureRequestTimeout'])->name('failures.request-timeout');
});

// Runtime Insight (clarityphp/runtime-insight): deliberate errors to test the package
Route::prefix('insight-test')->name('insight-test.')->group(function (): void {
    Route::get('/', [RuntimeInsightTestController::class, 'index'])->name('index');
    Route::get('/null-pointer', [RuntimeInsightTestController::class, 'nullPointer'])->name('null-pointer');
    Route::get('/undefined-index', [RuntimeInsightTestController::class, 'undefinedIndex'])->name('undefined-index');
    Route::get('/type-error', [RuntimeInsightTestController::class, 'typeError'])->name('type-error');
    Route::get('/argument-count', [RuntimeInsightTestController::class, 'argumentCount'])->name('argument-count');
    Route::get('/class-not-found', [RuntimeInsightTestController::class, 'classNotFound'])->name('class-not-found');
});
