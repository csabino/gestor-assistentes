<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rota do Chat Público em Pop-up (Definida primeiro para prioridade de leitura)
Route::get('/chat/{id}', [AssistantController::class, 'chat'])->name('assistant.chat');

// Rotas do Painel Principal
Route::get('/', [AssistantController::class, 'index']);
Route::post('/', [AssistantController::class, 'store']);
Route::put('/', [AssistantController::class, 'updateConfig']);
Route::patch('/', [AssistantController::class, 'toggleStatus']);
Route::delete('/', [AssistantController::class, 'destroy']);