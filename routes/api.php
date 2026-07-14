<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Loaded with the "/api" prefix and the "api" middleware group. Every module
| is versioned under /api/v1 and namespaced App\Http\Controllers\Api\V1 so a
| future /api/v2 can live alongside it without breaking existing clients.
|
| Responses use the standard App\Support\ApiResponse envelope (see the
| ApiResponser trait on App\Http\Controllers\Api\Controller). The "api" rate
| limiter is defined in App\Providers\AppServiceProvider.
|
| To protect a group once an API auth package (e.g. Laravel Sanctum) is
| installed, wrap it in ->middleware('auth:sanctum').
|
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('/health', HealthController::class)->name('health');
});
