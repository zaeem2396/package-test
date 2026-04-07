<?php

use App\Http\Controllers\KnowledgeDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KnowledgeDemoController::class, 'index'])->name('knowledge.demo');
Route::post('/knowledge/ask', [KnowledgeDemoController::class, 'ask'])->name('knowledge.ask');
Route::post('/knowledge/search', [KnowledgeDemoController::class, 'search'])->name('knowledge.search');
