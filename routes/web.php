<?php

use App\Http\Controllers\NatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('nats')->name('nats.')->group(function () {
    Route::get('/', [NatsController::class, 'dashboard'])->name('dashboard');
    Route::post('/publish', [NatsController::class, 'publish'])->name('publish');
    Route::post('/request-reply', [NatsController::class, 'requestReply'])->name('request-reply');
    Route::post('/queue/dispatch', [NatsController::class, 'dispatchJob'])->name('queue.dispatch');
    Route::post('/queue/delayed', [NatsController::class, 'dispatchDelayed'])->name('queue.delayed');
    Route::post('/queue/failing', [NatsController::class, 'dispatchFailing'])->name('queue.failing');
    Route::get('/streams', [NatsController::class, 'streams'])->name('streams');
    Route::get('/failed-jobs', [NatsController::class, 'failedJobs'])->name('failed-jobs');
    Route::get('/activity', [NatsController::class, 'activity'])->name('activity');
});
