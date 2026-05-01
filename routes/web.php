<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('projects', ProjectController::class)->only(['index', 'show', 'create', 'store']);

Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::post('/tasks/demo/fail-create', [TaskController::class, 'demoCreateFailure'])->name('tasks.demo.fail-create');
Route::put('/tasks/{task}/demo/fail-update', [TaskController::class, 'demoUpdateFailure'])->name('tasks.demo.fail-update');
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
});

Route::get('/demo/error', function () {
    throw new \RuntimeException('Intentional error for testing error handling and observability.');
})->name('demo.error');
