<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CalendarController;

// ROTA DE EMERGÊNCIA PARA LIMPAR O LIXO DO BANCO
Route::get('/emergencia', function () {
    try {
        DB::statement('UPDATE assistants SET knowledge_files = NULL');
        if (\Illuminate\Support\Facades\Schema::hasTable('webhook_logs')) {
            DB::statement('DELETE FROM webhook_logs');
        }
        return '<h1 style="color:green;">SISTEMA DESTRAVADO COM SUCESSO!</h1><p>Toda a sujeira de arquivos corrompidos foi removida do banco de dados.</p><a href="/">Voltar para o Painel</a>';
    } catch (\Exception $e) {
        return 'Erro: ' . $e->getMessage();
    }
});

Route::match(['get', 'post'], '/webhook/whatsapp/{id}', [AssistantController::class, 'webhook']);

Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', function (\Illuminate\Http\Request $request) {
    if ($request->input('view') === 'equipe') return app(AgentController::class)->handle($request);
    if ($request->input('view') === 'agenda') return app(CalendarController::class)->handle($request);
    return app(AssistantController::class)->index($request);
});