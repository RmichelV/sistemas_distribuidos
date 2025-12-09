<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DevolutionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API REST para el servicio de ventas
|
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'sales-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});

// Sales
Route::prefix('sales')->group(function () {
    Route::get('/', [SaleController::class, 'index']);
    Route::get('/{id}', [SaleController::class, 'show']);
    Route::post('/', [SaleController::class, 'store']);
    Route::get('/branch/{branchId}', [SaleController::class, 'getSalesByBranch']);
    Route::get('/branch/{branchId}/totals', [SaleController::class, 'getTotalsByBranch']);
    Route::post('/totals/by-date', [SaleController::class, 'getTotalsByDate']);
});

// Devolutions
Route::prefix('devolutions')->group(function () {
    Route::get('/', [DevolutionController::class, 'index']);
    Route::get('/{id}', [DevolutionController::class, 'show']);
    Route::post('/', [DevolutionController::class, 'store']);
    Route::get('/sale/{saleId}', [DevolutionController::class, 'getDevolutionsBySale']);
});
