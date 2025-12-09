<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

// Health check
Route::get('/health', [GatewayController::class, 'health']);

// Auth Service Routes - Autenticación y Usuarios
Route::any('/auth/login', [GatewayController::class, 'auth']);
Route::any('/auth/register', [GatewayController::class, 'auth']);
Route::any('/auth/logout', [GatewayController::class, 'auth']);
Route::any('/auth/refresh', [GatewayController::class, 'auth']);
Route::any('/auth/me', [GatewayController::class, 'auth']);
Route::any('/users/{path?}', [GatewayController::class, 'auth'])->where('path', '.*');
Route::any('/roles/{path?}', [GatewayController::class, 'auth'])->where('path', '.*');

// Branch Service Routes - Sucursales
Route::any('/branches/{path?}', [GatewayController::class, 'branches'])->where('path', '.*');

// Inventory Service Routes - Productos, Stock, Compras, Transferencias
Route::any('/products/{path?}', [GatewayController::class, 'inventory'])->where('path', '.*');
Route::any('/stock/{path?}', [GatewayController::class, 'inventory'])->where('path', '.*');
Route::any('/purchases/{path?}', [GatewayController::class, 'inventory'])->where('path', '.*');
Route::any('/stock-transfers/{path?}', [GatewayController::class, 'inventory'])->where('path', '.*');

// Sales Service Routes - Ventas y Devoluciones
Route::any('/sales/{path?}', [GatewayController::class, 'sales'])->where('path', '.*');
Route::any('/devolutions/{path?}', [GatewayController::class, 'sales'])->where('path', '.*');

// Reservations Service Routes - Clientes y Reservas
Route::any('/customers/{path?}', [GatewayController::class, 'reservations'])->where('path', '.*');
Route::any('/reservations/{path?}', [GatewayController::class, 'reservations'])->where('path', '.*');

// HR Service Routes - Recursos Humanos
Route::any('/employees/{path?}', [GatewayController::class, 'hr'])->where('path', '.*');
Route::any('/attendance/{path?}', [GatewayController::class, 'hr'])->where('path', '.*');
Route::any('/salary-adjustments/{path?}', [GatewayController::class, 'hr'])->where('path', '.*');

// Config Service Routes - Configuración y Tasas de Cambio
Route::any('/exchange-rates/{path?}', [GatewayController::class, 'config'])->where('path', '.*');
Route::any('/settings/{path?}', [GatewayController::class, 'config'])->where('path', '.*');
