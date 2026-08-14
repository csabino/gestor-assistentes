<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

// Rota Única da Raiz
Route::match(['get', 'post', 'patch', 'put', 'delete'], '/', [AssistantController::class, 'index']);