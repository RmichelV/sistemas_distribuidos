<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $products = Product::with(['productStore', 'productBranches'])
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(['productStore', 'productBranches'])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'code' => 'required|string|max:255|unique:products,code',
            'img_product' => 'nullable|string|max:500',
        ]);

        try {
            $product = Product::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'code' => 'sometimes|string|max:255|unique:products,code,'.$id,
            'img_product' => 'nullable|string|max:500',
        ]);

        try {
            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        try {
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchByCode(string $code): JsonResponse
    {
        $product = Product::with(['productStore', 'productBranches'])
            ->where('code', $code)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function getStockByBranch(string $productId, string $branchId): JsonResponse
    {
        $stock = \App\Models\ProductBranch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock no encontrado',
                'data' => ['quantity_in_stock' => 0]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stock
        ]);
    }
}
