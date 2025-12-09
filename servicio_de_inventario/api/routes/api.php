<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API REST para el servicio de inventario
|
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'inventory-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/code/{code}', [ProductController::class, 'searchByCode']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{productId}/stock/{branchId}', [ProductController::class, 'getStockByBranch']);
    Route::post('/', [ProductController::class, 'store']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});

// Purchases
Route::prefix('purchases')->group(function () {
    Route::get('/', [PurchaseController::class, 'index']);
    Route::get('/{id}', [PurchaseController::class, 'show']);
    Route::post('/', [PurchaseController::class, 'store']);
});

// Inventory
Route::prefix('inventory')->group(function () {
    Route::get('/branch/{branchId}', [InventoryController::class, 'getByBranch']);
    Route::post('/stock', [InventoryController::class, 'updateStock']);
    Route::post('/transfer', [InventoryController::class, 'transfer']);
});
