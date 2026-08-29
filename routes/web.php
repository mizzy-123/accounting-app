<?php

use App\Http\Controllers\EntityController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'entity.access'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Entity switcher
    Route::post('/entity/{entity}/switch', [EntityController::class, 'switch'])
        ->name('entity.switch');

    // Team management (hanya entity bisnis, hanya owner)
    Route::prefix('/entity/{entity}/team')->name('entity.team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::patch('/{user}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{user}', [TeamController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/settings.php';

