<?php

use App\Http\Controllers\Api\V1\ContentIngestionController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::post('/content', [ContentIngestionController::class, 'store'])
        ->middleware(['content.api.key', 'throttle:content-api'])
        ->name('api.v1.content.store');
});

Route::post('/webhooks/{provider}', [WebhookController::class, 'handle'])
    ->name('api.webhooks.handle');
