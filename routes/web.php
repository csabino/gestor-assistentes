<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

Route::get('/', [AssistantController::class, 'index'])->name('assistants.index');
Route::post('/', [AssistantController::class, 'store'])->name('assistants.store');
Route::patch('/', [AssistantController::class, 'toggleStatus'])->name('assistants.toggle');
Route::delete('/', [AssistantController::class, 'destroy'])->name('assistants.destroy');