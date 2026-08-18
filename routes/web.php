<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CalendarController;

// ROTA DE CAPTURA DE LOG (RAIO-X DO SERVIDOR)
Route::get('/debug-log', function () {
    $logPath = storage_path('logs/laravel.log');
    if (!File::exists($logPath)) {
        return "<h2 style='font-family:sans-serif'>Nenhum arquivo de log encontrado.</h2>";
    }
    $logContent = file($logPath);
    $lastLines = array_slice($logContent, -150);
    return '<pre style="background:#111;color:#0f0;padding:20px;white-space:pre-wrap;font-size:12px;overflow-x:auto;">' . htmlspecialchars(implode("", $lastLines)) . '</pre>';
});

// Libera o WhatsApp
Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Rotas Principais
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', function (\Illuminate\Http\Request $request) {
    if ($request->input('view') === 'equipe') return app(AgentController::class)->handle($request);
    if ($request->input('view') === 'agenda') return app(CalendarController::class)->handle($request);
    return app(AssistantController::class)->index($request);
});