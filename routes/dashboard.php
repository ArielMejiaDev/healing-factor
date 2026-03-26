<?php

use ArielMejiaDev\HealingFactor\Http\Controllers\DashboardController;
use ArielMejiaDev\HealingFactor\Http\Middleware\AuthorizeDashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(
    config('healing-factor.dashboard.middleware', ['web', 'auth']),
    [AuthorizeDashboard::class]
))
    ->prefix(config('healing-factor.dashboard.path', 'healing-factor'))
    ->name('healing-factor.dashboard.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/{issue}', [DashboardController::class, 'show'])->name('show');
        Route::post('/{issue}/retry', [DashboardController::class, 'retry'])->name('retry');
        Route::post('/{issue}/mark-failed', [DashboardController::class, 'markFailed'])->name('mark-failed');
    });
