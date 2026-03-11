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

Route::resource('projects', ProjectController::class)->only(['index', 'show', 'store']);

Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
});
