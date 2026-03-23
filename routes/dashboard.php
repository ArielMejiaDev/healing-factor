<?php

use ArielMejiaDev\XFactor\Http\Controllers\DashboardController;
use ArielMejiaDev\XFactor\Http\Middleware\AuthorizeDashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(
    config('x-factor.dashboard.middleware', ['web', 'auth']),
    [AuthorizeDashboard::class]
))
    ->prefix(config('x-factor.dashboard.path', 'x-factor'))
    ->name('x-factor.dashboard.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/{issue}', [DashboardController::class, 'show'])->name('show');
        Route::post('/{issue}/retry', [DashboardController::class, 'retry'])->name('retry');
        Route::post('/{issue}/mark-failed', [DashboardController::class, 'markFailed'])->name('mark-failed');
    });
