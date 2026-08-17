<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CalendarController;

// ESSENCIAL: Isso tira a barreira de segurança que estava barrando o WhatsApp de falar com seu painel!
Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', function (\Illuminate\Http\Request $request) {
    if ($request->input('view') === 'equipe') return app(AgentController::class)->handle($request);
    if ($request->input('view') === 'agenda') return app(CalendarController::class)->handle($request);
    return app(AssistantController::class)->index($request);
});