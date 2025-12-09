<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    /**
     * Autenticación con JWT
     * 
     * POST /api/auth/login
     * Body: { "email": "user@ewtto.com", "password": "password123" }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Registrar nuevo usuario (opcional, según reglas de negocio)
     * 
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        // Validación básica (puedes crear un RegisterRequest separado)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|regex:/@ewtto\.com$/i',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required|integer',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'branch_id' => $request->branch_id,
            'role_id' => $request->role_id,
            'address' => $request->address ?? '',
            'phone' => $request->phone ?? '',
            'base_salary' => $request->base_salary ?? 500,
            'hire_date' => $request->hire_date ?? now(),
        ]);

        $token = auth()->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * Obtener usuario autenticado
     * 
     * GET /api/auth/me
     * Header: Authorization: Bearer {token}
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();
        $user->load('role'); // Cargar relación de rol

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Cerrar sesión (invalidar token)
     * 
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Refrescar token
     * 
     * POST /api/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Estructura de respuesta con token
     */
    protected function respondWithToken($token): JsonResponse
    {
        $user = auth()->user();
        $user->load('role');

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name
                ]
            ]
        ]);
    }
}
