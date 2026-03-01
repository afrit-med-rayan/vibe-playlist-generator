<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VibeController;
use Illuminate\Support\Facades\Route;

// ── Public ───────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
});

// Auth
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [VibeController::class, 'dashboard'])->name('dashboard');
    Route::post('/vibe/analyze', [VibeController::class, 'analyze'])->name('vibe.analyze');
    Route::get('/vibe/result/{session}', [VibeController::class, 'result'])->name('vibe.result');
    Route::post('/vibe/playlist/{session}', [VibeController::class, 'createPlaylist'])->name('vibe.create-playlist');
    Route::get('/vibe/history', [VibeController::class, 'history'])->name('vibe.history');
});