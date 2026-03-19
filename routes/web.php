<?php

declare(strict_types=1);

use App\Http\Controllers\ConductorPocController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Conductor / Orkes Laravel — full feature PoC (see CONDUCTOR_POC_TESTING.md)
|--------------------------------------------------------------------------
*/
Route::prefix('conductor-poc')->name('conductor-poc.')->group(function (): void {
    Route::get('/', [ConductorPocController::class, 'index'])->name('index');
    Route::get('/definition/preview', [ConductorPocController::class, 'definitionPreview'])->name('definition.preview');

    Route::post('/workflow/register', [ConductorPocController::class, 'registerWorkflow'])->name('workflow.register');
    Route::post('/workflow/update', [ConductorPocController::class, 'updateWorkflow'])->name('workflow.update');
    Route::post('/workflow/start', [ConductorPocController::class, 'startWorkflow'])->name('workflow.start');

    Route::get('/workflow/{id}', [ConductorPocController::class, 'showWorkflow'])->name('workflow.show');
    Route::get('/workflow/{id}/status', [ConductorPocController::class, 'workflowStatus'])->name('workflow.status');

    Route::post('/workflow/{id}/terminate', [ConductorPocController::class, 'terminateWorkflow'])->name('workflow.terminate');
    Route::post('/workflow/{id}/pause', [ConductorPocController::class, 'pauseWorkflow'])->name('workflow.pause');
    Route::post('/workflow/{id}/resume', [ConductorPocController::class, 'resumeWorkflow'])->name('workflow.resume');
    Route::post('/workflow/{id}/retry', [ConductorPocController::class, 'retryWorkflow'])->name('workflow.retry');

    Route::get('/search/running', [ConductorPocController::class, 'searchRunning'])->name('search.running');
    Route::get('/search/failed', [ConductorPocController::class, 'searchFailed'])->name('search.failed');

    Route::post('/tasks/poll', [ConductorPocController::class, 'pollTask'])->name('tasks.poll');
});
