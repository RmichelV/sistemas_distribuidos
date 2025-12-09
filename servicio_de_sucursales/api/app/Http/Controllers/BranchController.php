<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Http\Requests\Branches\BranchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Listar todas las sucursales
     * GET /api/branches
     */
    public function index(): JsonResponse
    {
        $branches = Branch::orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Mostrar sucursal específica
     * GET /api/branches/{id}
     */
    public function show(string $id): JsonResponse
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Sucursal no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }

    /**
     * Crear nueva sucursal
     * POST /api/branches
     */
    public function store(BranchRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $branch = Branch::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Sucursal creada exitosamente',
                'data' => $branch
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar sucursal
     * PUT /api/branches/{id}
     */
    public function update(BranchRequest $request, string $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $data = $request->validated();

        try {
            if (isset($data['name'])) $branch->name = $data['name'];
            if (isset($data['address'])) $branch->address = $data['address'];

            $branch->save();

            return response()->json([
                'success' => true,
                'message' => 'Sucursal actualizada exitosamente',
                'data' => $branch
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar sucursal
     * DELETE /api/branches/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Sucursal no encontrada'
            ], 404);
        }

        try {
            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sucursal eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar existencia de sucursal (para validaciones HTTP)
     * GET /api/branches/{id}/exists
     */
    public function exists(string $id): JsonResponse
    {
        $exists = Branch::where('id', $id)->exists();

        return response()->json([
            'exists' => $exists
        ]);
    }
}
