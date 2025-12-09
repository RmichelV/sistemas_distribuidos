<?php

namespace App\Http\Controllers;

use App\Models\ProductBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function getByBranch(string $branchId): JsonResponse
    {
        $inventory = ProductBranch::with('product')
            ->where('branch_id', $branchId)
            ->orderBy('product_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    public function updateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'quantity_in_stock' => 'required|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'units_per_box' => 'nullable|integer|min:1',
        ]);

        try {
            $stock = ProductBranch::updateOrCreate(
                [
                    'product_id' => $validated['product_id'],
                    'branch_id' => $validated['branch_id']
                ],
                array_merge($validated, ['last_update' => now()->toDateString()])
            );

            $stock->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
                'data' => $stock
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'from_branch_id' => 'required|integer',
            'to_branch_id' => 'required|integer|different:from_branch_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            // Verificar stock origen
            $fromStock = ProductBranch::where('product_id', $validated['product_id'])
                ->where('branch_id', $validated['from_branch_id'])
                ->first();

            if (!$fromStock || $fromStock->quantity_in_stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente en sucursal de origen'
                ], 400);
            }

            // Reducir stock origen
            $fromStock->quantity_in_stock -= $validated['quantity'];
            $fromStock->last_update = now()->toDateString();
            $fromStock->save();

            // Aumentar stock destino
            $toStock = ProductBranch::firstOrNew([
                'product_id' => $validated['product_id'],
                'branch_id' => $validated['to_branch_id']
            ]);

            $toStock->quantity_in_stock = ($toStock->quantity_in_stock ?? 0) + $validated['quantity'];
            $toStock->unit_price = $fromStock->unit_price;
            $toStock->last_update = now()->toDateString();
            $toStock->save();

            return response()->json([
                'success' => true,
                'message' => 'Transferencia realizada exitosamente',
                'data' => [
                    'from' => $fromStock,
                    'to' => $toStock
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
