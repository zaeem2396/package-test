<?php

use App\Http\Controllers\VectoraStudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/vectora/studio', [VectoraStudioController::class, 'index'])->name('vectora.studio');

Route::prefix('vectora/api')->group(function () {
    Route::get('/stats', [VectoraStudioController::class, 'stats']);
    Route::post('/upsert', [VectoraStudioController::class, 'upsert']);
    Route::post('/query', [VectoraStudioController::class, 'query']);
    Route::post('/delete', [VectoraStudioController::class, 'deleteVectors']);
});
