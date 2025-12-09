<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\InventoryController;

// Health check
Route::get('/api/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'inventory-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});

// Products
Route::prefix('api/products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/code/{code}', [ProductController::class, 'searchByCode']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{productId}/stock/{branchId}', [ProductController::class, 'getStockByBranch']);
    Route::post('/', [ProductController::class, 'store']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});

// Purchases
Route::prefix('api/purchases')->group(function () {
    Route::get('/', [PurchaseController::class, 'index']);
    Route::get('/{id}', [PurchaseController::class, 'show']);
    Route::post('/', [PurchaseController::class, 'store']);
});

// Inventory
Route::prefix('api/inventory')->group(function () {
    Route::get('/branch/{branchId}', [InventoryController::class, 'getByBranch']);
    Route::post('/stock', [InventoryController::class, 'updateStock']);
    Route::post('/transfer', [InventoryController::class, 'transfer']);
});
