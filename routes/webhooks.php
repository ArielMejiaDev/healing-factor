<?php

use ArielMejiaDev\HealingFactor\Http\Controllers\WebhookController;
use ArielMejiaDev\HealingFactor\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post(config('healing-factor.webhook.path', 'healing-factor/webhook'), WebhookController::class)
    ->middleware(array_merge(
        [VerifyWebhookSignature::class],
        config('healing-factor.webhook.middleware', []),
    ))
    ->name('healing-factor.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);
