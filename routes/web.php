<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Suas rotas atuais de controle do painel
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);
Route::post('/', [AssistantController::class, 'store']);
Route::put('/', [AssistantController::class, 'updateConfig']);
Route::patch('/', [AssistantController::class, 'toggleStatus']);
Route::delete('/', [AssistantController::class, 'destroy']);

// NOVA ROTA: O Chat Público do Assistente
Route::get('/chat/{id}', [AssistantController::class, 'chat']);