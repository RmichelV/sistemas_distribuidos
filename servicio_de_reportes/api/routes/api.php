<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API REST para el servicio de autenticación y usuarios
|
*/

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Rutas protegidas con JWT
Route::middleware('auth:api')->group(function () {
    
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Gestión de usuarios
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);           // Listar usuarios
        Route::post('/', [UserController::class, 'store']);          // Crear usuario
        Route::get('/{id}', [UserController::class, 'show']);        // Ver usuario
        Route::put('/{id}', [UserController::class, 'update']);      // Actualizar usuario
        Route::delete('/{id}', [UserController::class, 'destroy']);  // Eliminar usuario
        Route::post('/switch-branch', [UserController::class, 'switchBranch']); // Cambiar sucursal
    });

    // Gestión de roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);           // Listar roles
        Route::post('/', [RoleController::class, 'store']);          // Crear rol
        Route::get('/{id}', [RoleController::class, 'show']);        // Ver rol
        Route::put('/{id}', [RoleController::class, 'update']);      // Actualizar rol
        Route::delete('/{id}', [RoleController::class, 'destroy']);  // Eliminar rol
    });
});

// Ruta de health check
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'service' => 'auth-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});
