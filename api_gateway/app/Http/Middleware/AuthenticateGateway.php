<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthenticateGateway
{
    public function handle(Request $request, Closure $next)
    {
        // Rutas públicas que no requieren autenticación
        $publicRoutes = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/health',
            '/api/config/exchange-rates/current',
            '/api/config/settings/public',
        ];

        $path = $request->path();
        
        // Verificar si es una ruta pública
        foreach ($publicRoutes as $route) {
            if (str_starts_with('/' . $path, $route)) {
                return $next($request);
            }
        }

        // Verificar token JWT
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado'
            ], 401);
        }

        // Validar token con el servicio de autenticación
        try {
            $authUrl = env('AUTH_SERVICE_URL');
            $response = Http::timeout(5)
                ->withToken($token)
                ->get($authUrl . '/api/auth/me');

            if ($response->successful()) {
                // Agregar usuario al request
                $request->merge(['user' => $response->json()]);
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
