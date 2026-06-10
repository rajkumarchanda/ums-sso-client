<?php

use Illuminate\Support\Facades\Route;
use SmartExam\SsoClient\Http\Controllers\SsoCallbackController;
use SmartExam\SsoClient\Http\Controllers\SsoExchangeController;

$prefix = config('smartexam-sso.routes.prefix', '');
$middleware = config('smartexam-sso.routes.middleware', ['web']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () {
        Route::get(
            config('smartexam-sso.routes.callback', 'sso/callback'),
            SsoCallbackController::class
        )->name('smartexam-sso.callback');

        Route::post(
            config('smartexam-sso.routes.exchange', 'api/sso/exchange'),
            SsoExchangeController::class
        )->name('smartexam-sso.exchange');
    });
