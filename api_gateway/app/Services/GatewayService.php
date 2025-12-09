<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GatewayService
{
    private array $services = [
        'auth' => 'AUTH_SERVICE_URL',
        'branches' => 'BRANCH_SERVICE_URL',
        'inventory' => 'INVENTORY_SERVICE_URL',
        'sales' => 'SALES_SERVICE_URL',
        'reservations' => 'RESERVATIONS_SERVICE_URL',
        'hr' => 'HR_SERVICE_URL',
        'config' => 'CONFIG_SERVICE_URL',
    ];

    public function proxy(string $service, string $method, string $path, array $data = [], ?string $token = null)
    {
        if (!isset($this->services[$service])) {
            return [
                'status' => 404,
                'body' => ['message' => 'Servicio no encontrado']
            ];
        }

        $serviceUrl = env($this->services[$service]);
        $url = rtrim($serviceUrl, '/') . '/' . ltrim($path, '/');

        $request = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        if ($token) {
            $request->withToken($token);
        }

        try {
            $response = match(strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'PATCH' => $request->patch($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => throw new \Exception('Método HTTP no soportado')
            };

            return [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'headers' => $response->headers()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'body' => [
                    'message' => 'Error al comunicarse con el servicio',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    public function getServiceUrl(string $service): ?string
    {
        if (!isset($this->services[$service])) {
            return null;
        }
        
        return env($this->services[$service]);
    }
}
