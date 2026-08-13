<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;

Route::get('/', [AssistantController::class, 'index'])->name('assistants.index');
Route::get('/assistants', function () {
    return redirect('/');
});
Route::post('/assistants', [AssistantController::class, 'store'])->name('assistants.store');