<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/spotify', [AuthController::class, 'redirect'])->name('auth.spotify');
Route::get('/auth/spotify/callback', [AuthController::class, 'callback']);

Route::get('/dashboard', function () {
    return "Welcome " . auth()->user()->name;
})->middleware('auth');