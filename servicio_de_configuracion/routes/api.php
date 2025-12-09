<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\SettingController;

// Health check
Route::get('/health', function () {
    return response()->json([
        'service' => 'config-service',
        'status' => 'healthy'
    ]);
});

// Exchange Rates (Tasas de cambio USD)
Route::prefix('exchange-rates')->group(function () {
    Route::get('/', [ExchangeRateController::class, 'index']);
    Route::get('/current', [ExchangeRateController::class, 'current']);
    Route::get('/history', [ExchangeRateController::class, 'history']);
    Route::get('/date/{date}', [ExchangeRateController::class, 'getByDate']);
    Route::get('/{id}', [ExchangeRateController::class, 'show']);
    Route::post('/', [ExchangeRateController::class, 'store']);
    Route::put('/{id}', [ExchangeRateController::class, 'update']);
    Route::put('/{id}/deactivate', [ExchangeRateController::class, 'deactivate']);
    Route::delete('/{id}', [ExchangeRateController::class, 'destroy']);
});

// System Settings (Configuraciones del sistema)
Route::prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::get('/public', [SettingController::class, 'publicSettings']);
    Route::get('/categories', [SettingController::class, 'categories']);
    Route::get('/key/{key}', [SettingController::class, 'getByKey']);
    Route::get('/category/{category}', [SettingController::class, 'getByCategory']);
    Route::get('/{id}', [SettingController::class, 'show']);
    Route::post('/', [SettingController::class, 'store']);
    Route::put('/{id}', [SettingController::class, 'update']);
    Route::put('/key/{key}/value', [SettingController::class, 'updateByKey']);
    Route::put('/category/{category}/bulk', [SettingController::class, 'bulkUpdateByCategory']);
    Route::delete('/{id}', [SettingController::class, 'destroy']);
});
