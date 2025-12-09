<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::with('product')->orderBy('purchase_date', 'desc');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        $purchases = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'purchase_quantity' => 'required|integer|min:1',
            'purchase_date' => 'required|date',
            'branch_id' => 'required|integer',
            'unit_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $purchase = Purchase::create($validated);
            $purchase->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Compra registrada exitosamente',
                'data' => $purchase
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $purchase = Purchase::with('product')->find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $purchase
        ]);
    }
}
