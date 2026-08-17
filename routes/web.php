<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AgentController;

// Webhook do WhatsApp (Suporte a rota por subpasta)
Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook']);

// Central de Roteamento Imune a Erros 404 do Nginx
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', function (\Illuminate\Http\Request $request) {
    if ($request->input('view') === 'equipe') {
        return app(AgentController::class)->handle($request);
    }
    return app(AssistantController::class)->index($request);
});