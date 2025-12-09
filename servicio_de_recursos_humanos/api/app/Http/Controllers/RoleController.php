<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{

    /**
     * Listar todos los roles
     * 
     * GET /api/roles
     */
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Obtener un rol específico
     * 
     * GET /api/roles/{id}
     */
    public function show(string $id): JsonResponse
    {
        $role = Role::withCount('users')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * Crear nuevo rol
     * 
     * POST /api/roles
     * Body: { "name": "Vendedor" }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ], [
            'name.required' => 'El nombre del rol es requerido',
            'name.unique' => 'Ya existe un rol con ese nombre'
        ]);

        try {
            $role = Role::create([
                'name' => $request->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rol creado exitosamente',
                'data' => $role
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar rol existente
     * 
     * PUT/PATCH /api/roles/{id}
     * Body: { "name": "Vendedor Senior" }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id
        ], [
            'name.required' => 'El nombre del rol es requerido',
            'name.unique' => 'Ya existe un rol con ese nombre'
        ]);

        try {
            $role->name = $request->name;
            $role->save();

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado exitosamente',
                'data' => $role
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar rol
     * 
     * DELETE /api/roles/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        // Verificar si el rol tiene usuarios asignados
        $usersCount = $role->users()->count();
        
        if ($usersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el rol porque tiene {$usersCount} usuario(s) asignado(s)"
            ], 422);
        }

        try {
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
