<?php

namespace App\Http\Controllers;

use App\Models\Devolution;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevolutionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Devolution::with('sale');

        if ($request->has('sale_id')) {
            $query->where('sale_id', $request->sale_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $devolutions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $devolutions
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $devolution = Devolution::with('sale')->find($id);

        if (!$devolution) {
            return response()->json([
                'success' => false,
                'message' => 'Devolución no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $devolution
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer|exists:sales,id',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
            'refund_amount' => 'required|numeric|min:0',
        ]);

        try {
            // Verificar que el producto esté en la venta
            $saleItem = SaleItem::where('sale_id', $validated['sale_id'])
                ->where('product_id', $validated['product_id'])
                ->first();

            if (!$saleItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'El producto no existe en esta venta'
                ], 400);
            }

            // Verificar cantidad ya devuelta
            $alreadyReturned = Devolution::where('sale_id', $validated['sale_id'])
                ->where('product_id', $validated['product_id'])
                ->sum('quantity');

            if ($alreadyReturned + $validated['quantity'] > $saleItem->quantity_products) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad a devolver excede la cantidad comprada'
                ], 400);
            }

            $devolution = Devolution::create($validated);
            $devolution->load('sale');

            return response()->json([
                'success' => true,
                'message' => 'Devolución registrada exitosamente',
                'data' => $devolution
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar devolución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDevolutionsBySale(string $saleId): JsonResponse
    {
        $devolutions = Devolution::where('sale_id', $saleId)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRefunded = $devolutions->sum('refund_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'devolutions' => $devolutions,
                'total_refunded' => $totalRefunded
            ]
        ]);
    }
}
