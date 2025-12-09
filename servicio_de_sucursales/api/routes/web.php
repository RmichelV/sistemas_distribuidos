<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;

// Health check
Route::get('/api/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'branch-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});

// Branch endpoints (sin autenticación para comunicación entre servicios)
Route::prefix('api/branches')->group(function () {
    Route::get('/', [BranchController::class, 'index']);
    Route::get('/{id}', [BranchController::class, 'show']);
    Route::get('/{id}/exists', [BranchController::class, 'exists']);
    Route::post('/', [BranchController::class, 'store']);
    Route::put('/{id}', [BranchController::class, 'update']);
    Route::delete('/{id}', [BranchController::class, 'destroy']);
});
