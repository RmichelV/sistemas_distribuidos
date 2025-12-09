<?php

namespace App\Http\Controllers;

use App\Services\GatewayService;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    protected GatewayService $gateway;

    public function __construct(GatewayService $gateway)
    {
        $this->gateway = $gateway;
    }

    // Auth Service - Autenticación y Usuarios
    public function auth(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        
        // Obtener la ruta completa después de /api/
        $fullPath = $request->path(); // Ejemplo: api/auth/login
        
        $result = $this->gateway->proxy(
            'auth',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // Branch Service - Sucursales
    public function branches(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        $fullPath = $request->path();
        
        $result = $this->gateway->proxy(
            'branches',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // Inventory Service - Productos, Stock, Compras
    public function inventory(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        $fullPath = $request->path();
        
        $result = $this->gateway->proxy(
            'inventory',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // Sales Service - Ventas y Devoluciones
    public function sales(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        $fullPath = $request->path();
        
        $result = $this->gateway->proxy(
            'sales',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // Reservations Service - Clientes y Reservas
    public function reservations(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        $fullPath = $request->path();
        
        $result = $this->gateway->proxy(
            'reservations',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // HR Service - Recursos Humanos
    public function hr(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        $fullPath = $request->path();
        
        $result = $this->gateway->proxy(
            'hr',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // Config Service - Configuración
    public function config(Request $request, string $path = '')
    {
        $token = $request->bearerToken();
        $fullPath = $request->path();
        
        $result = $this->gateway->proxy(
            'config',
            $request->method(),
            $fullPath,
            $request->all(),
            $token
        );

        return response()->json($result['body'], $result['status']);
    }

    // Health check del gateway
    public function health()
    {
        $services = [
            'auth' => env('AUTH_SERVICE_URL'),
            'branches' => env('BRANCH_SERVICE_URL'),
            'inventory' => env('INVENTORY_SERVICE_URL'),
            'sales' => env('SALES_SERVICE_URL'),
            'reservations' => env('RESERVATIONS_SERVICE_URL'),
            'hr' => env('HR_SERVICE_URL'),
            'config' => env('CONFIG_SERVICE_URL'),
        ];

        $status = [];
        foreach ($services as $name => $url) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(2)->get($url . '/api/health');
                $status[$name] = $response->successful() ? 'healthy' : 'unhealthy';
            } catch (\Exception $e) {
                $status[$name] = 'unreachable';
            }
        }

        return response()->json([
            'gateway' => 'healthy',
            'services' => $status
        ]);
    }
}
