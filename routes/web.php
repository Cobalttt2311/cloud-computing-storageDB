<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [RegisterController::class, 'create'])->name('register.create');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::get('/pelamar', [RegisterController::class, 'index'])->name('register.index');
Route::get('/pelamar/{id}/edit', [RegisterController::class, 'edit'])->name('register.edit');
Route::put('/pelamar/{id}', [RegisterController::class, 'update'])->name('register.update');
Route::delete('/pelamar/{id}', [RegisterController::class, 'destroy'])->name('register.destroy');