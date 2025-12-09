<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'config-service',
        'version' => '1.0.0',
        'message' => 'API de configuración del sistema'
    ]);
});
