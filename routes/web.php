<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rotas do Painel Principal
Route::get('/', [AssistantController::class, 'index']);
Route::post('/', [AssistantController::class, 'store']);
Route::put('/', [AssistantController::class, 'updateConfig']);
Route::patch('/', [AssistantController::class, 'toggleStatus']);
Route::delete('/', [AssistantController::class, 'destroy']);

// Rota do Chat Público (Pop-up)
Route::get('/chat/{id}', [AssistantController::class, 'chat'])->name('assistant.chat');