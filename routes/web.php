<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rota dedicada de Webhook (Subpasta)
Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook']);

// Rota Principal e Webhook por parâmetro (?webhook_id=X)
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);