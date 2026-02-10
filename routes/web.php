<?php

use App\Http\Controllers\NatsController;
use App\Http\Controllers\RuntimeInsightTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
Route::get('/welcome', fn () => view('welcome'))->name('welcome');

Route::get('/dashboard', [NatsController::class, 'dashboard'])->name('dashboard');
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

// Runtime Insight (clarityphp/runtime-insight): deliberate errors to test the package
Route::prefix('insight-test')->name('insight-test.')->group(function (): void {
    Route::get('/', [RuntimeInsightTestController::class, 'index'])->name('index');
    Route::get('/null-pointer', [RuntimeInsightTestController::class, 'nullPointer'])->name('null-pointer');
    Route::get('/undefined-index', [RuntimeInsightTestController::class, 'undefinedIndex'])->name('undefined-index');
    Route::get('/type-error', [RuntimeInsightTestController::class, 'typeError'])->name('type-error');
    Route::get('/argument-count', [RuntimeInsightTestController::class, 'argumentCount'])->name('argument-count');
    Route::get('/class-not-found', [RuntimeInsightTestController::class, 'classNotFound'])->name('class-not-found');
});
