<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'API Gateway E-WTTO',
        'version' => '1.0.0',
        'status' => 'online',
        'endpoints' => [
            'health' => '/api/health',
            'auth' => '/api/auth/*',
            'branches' => '/api/branches/*',
            'inventory' => '/api/inventory/*',
            'sales' => '/api/sales/*',
            'reservations' => '/api/reservations/*',
            'hr' => '/api/hr/*',
            'config' => '/api/config/*',
        ]
    ]);
});
