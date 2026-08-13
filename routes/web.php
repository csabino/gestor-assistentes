<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rota principal (Tela inicial)
Route::get('/', [AssistantController::class, 'index'])->name('assistants.index');

// Rota para salvar um novo assistente no banco
Route::post('/assistants', [AssistantController::class, 'store'])->name('assistants.store');