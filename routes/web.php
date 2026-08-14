<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);
Route::post('/', [AssistantController::class, 'store']);
Route::put('/', [AssistantController::class, 'updateConfig']);
Route::patch('/', [AssistantController::class, 'toggleStatus']);
Route::delete('/', [AssistantController::class, 'destroy']);

// Rota do Chat Público (Pop-up)
Route::get('/chat/{id}', [AssistantController::class, 'chat']);