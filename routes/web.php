<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\OmniController;

Route::match(['get', 'post', 'patch', 'put', 'delete'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', function (\Illuminate\Http\Request $request) {
    
    // INTERCEPTA RIGOROSAMENTE A URL COM ?webhook_id=1
    if ($request->query('webhook_id')) {
        return app(AssistantController::class)->webhook($request, $request->query('webhook_id'));
    }

    if ($request->input('view') === 'equipe') return app(AgentController::class)->handle($request);
    if ($request->input('view') === 'agenda') return app(CalendarController::class)->handle($request);
    return app(AssistantController::class)->index($request);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/omni/send', [OmniController::class, 'forwardToOmni'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);