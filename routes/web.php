<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VibeController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('welcome');
});

// Spotify OAuth
Route::get('/auth/spotify', [AuthController::class, 'redirect'])->name('auth.spotify');
Route::get('/auth/spotify/callback', [AuthController::class, 'callback']);

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [VibeController::class, 'dashboard'])->name('dashboard');
    Route::post('/vibe/analyze', [VibeController::class, 'analyze'])->name('vibe.analyze');
    Route::get('/vibe/result/{session}', [VibeController::class, 'result'])->name('vibe.result');
    Route::post('/vibe/playlist/{session}', [VibeController::class, 'createPlaylist'])->name('vibe.create-playlist');
    Route::get('/vibe/history', [VibeController::class, 'history'])->name('vibe.history');
});