<?php

use ArielMejiaDev\XFactor\Http\Controllers\WebhookController;
use ArielMejiaDev\XFactor\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post(config('x-factor.webhook.path', 'x-factor/webhook'), WebhookController::class)
    ->middleware(array_merge(
        [VerifyWebhookSignature::class],
        config('x-factor.webhook.middleware', []),
    ))
    ->name('x-factor.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);
