<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AgentController;

// Rotas da Equipe e Agendas (Fase 6)
Route::get('/equipe', [AgentController::class, 'index']);
Route::post('/equipe/departamento', [AgentController::class, 'storeDepartment']);
Route::delete('/equipe/departamento', [AgentController::class, 'destroyDepartment']);
Route::post('/equipe/agente', [AgentController::class, 'storeAgent']);
Route::delete('/equipe/agente', [AgentController::class, 'destroyAgent']);

// Webhook do WhatsApp
Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook']);

// Rotas do Gestor de Assistentes (IA)
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);