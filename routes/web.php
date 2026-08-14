<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rota Única da Raiz (Trata Painel, Ações AJAX e Chat sem erro 404 de Nginx)
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);