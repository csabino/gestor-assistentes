<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rota do Webhook do WhatsApp (Aceita GET para validação e POST para mensagens)
Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook']);

// Rota Única da Raiz
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);