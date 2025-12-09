<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Auth Service API',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => '/api/auth/*',
            'users' => '/api/users/*',
            'roles' => '/api/roles/*',
            'health' => '/api/health'
        ]
    ]);
});
