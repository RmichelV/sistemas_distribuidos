<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    /**
     * Listar usuarios (filtrado por branch_id si se envía)
     * 
     * GET /api/users
     * GET /api/users?branch_id=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role');

        // Filtro por sucursal
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filtro por rol
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Búsqueda por nombre o email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Obtener un usuario específico
     * 
     * GET /api/users/{id}
     */
    public function show(string $id): JsonResponse
    {
        $user = User::with('role')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Crear nuevo usuario
     * 
     * POST /api/users
     * Body: {
     *   "name": "Juan Pérez",
     *   "email": "juan@ewtto.com",
     *   "password": "password123",
     *   "password_confirmation": "password123",
     *   "address": "Calle 123",
     *   "phone": "12345678",
     *   "branch_id": 1,
     *   "role_id": 2,
     *   "base_salary": 5000,
     *   "hire_date": "2025-01-01"
     * }
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $user = User::create([
                'name' => $data['name'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'role_id' => $data['role_id'],
                'branch_id' => $data['branch_id'],
                'base_salary' => $data['base_salary'],
                'hire_date' => $data['hire_date'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Cargar relación de rol
            $user->load('role');

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario existente
     * 
     * PUT/PATCH /api/users/{id}
     * Body: { "name": "Juan Pérez Actualizado", ... }
     */
    public function update(UserRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        try {
            // Actualizar solo los campos enviados
            if (isset($data['name'])) $user->name = $data['name'];
            if (isset($data['address'])) $user->address = $data['address'];
            if (isset($data['phone'])) $user->phone = $data['phone'];
            if (isset($data['role_id'])) $user->role_id = $data['role_id'];
            if (isset($data['branch_id'])) $user->branch_id = $data['branch_id'];
            if (isset($data['base_salary'])) $user->base_salary = $data['base_salary'];
            if (isset($data['hire_date'])) $user->hire_date = $data['hire_date'];
            if (isset($data['email'])) $user->email = $data['email'];

            // Solo actualizar password si se envía
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();
            $user->load('role');

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario
     * 
     * DELETE /api/users/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Evitar que un usuario se elimine a sí mismo
        $authUser = auth()->user();
        if ($authUser && $authUser->id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        try {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar sucursal del usuario autenticado
     * 
     * POST /api/users/switch-branch
     * Body: { "branch_id": 2 }
     */
    public function switchBranch(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer',
        ]);

        $user = auth()->user();
        $user->branch_id = $request->input('branch_id');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Sucursal actualizada exitosamente',
            'data' => [
                'branch_id' => $user->branch_id
            ]
        ]);
    }

    /**
     * Listar roles disponibles
     * 
     * GET /api/roles
     */
    public function roles(): JsonResponse
    {
        $roles = Role::all();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }
}
