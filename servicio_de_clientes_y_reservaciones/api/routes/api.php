<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReservationController;

// Health check
Route::get('/health', function () {
    return response()->json([
        'service' => 'reservations-service',
        'status' => 'healthy'
    ]);
});

// Customers
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});

// Reservations
Route::prefix('reservations')->group(function () {
    Route::get('/', [ReservationController::class, 'index']);
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/pending', [ReservationController::class, 'getPendingReservations']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::put('/{id}/status', [ReservationController::class, 'updateStatus']);
    Route::put('/{id}/payment', [ReservationController::class, 'updatePayment']);
    Route::get('/customer/{customerId}', [ReservationController::class, 'getByCustomer']);
    Route::get('/branch/{branchId}', [ReservationController::class, 'getByBranch']);
    Route::get('/branch/{branchId}/totals', [ReservationController::class, 'getTotalsByBranch']);
});
