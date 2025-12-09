<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with('saleItems');

        // Filtros opcionales
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->has('customer_name')) {
            $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
        }

        $sales = $query->orderBy('sale_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $sale = Sale::with('saleItems', 'devolutions')->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'sale_date' => 'required|date',
            'pay_type' => 'required|string|in:efectivo,tarjeta,transferencia',
            'branch_id' => 'required|integer',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity_products' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Generar código de venta único
            $saleCode = 'V-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 6, '0', STR_PAD_LEFT);

            // Calcular precio final
            $finalPrice = 0;
            foreach ($validated['items'] as $item) {
                $finalPrice += $item['unit_price'] * $item['quantity_products'];
            }

            // Crear venta
            $sale = Sale::create([
                'sale_code' => $saleCode,
                'customer_name' => $validated['customer_name'],
                'sale_date' => $validated['sale_date'],
                'pay_type' => $validated['pay_type'],
                'final_price' => $finalPrice,
                'exchange_rate' => $validated['exchange_rate'] ?? 1.0000,
                'notes' => $validated['notes'] ?? null,
                'branch_id' => $validated['branch_id'],
            ]);

            // Crear items de venta
            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity_products' => $item['quantity_products'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['unit_price'] * $item['quantity_products'],
                    'exchange_rate' => $validated['exchange_rate'] ?? 1.0000,
                ]);
            }

            DB::commit();

            $sale->load('saleItems');

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'data' => $sale
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSalesByBranch(string $branchId): JsonResponse
    {
        $sales = Sale::with('saleItems')
            ->where('branch_id', $branchId)
            ->orderBy('sale_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function getTotalsByBranch(string $branchId): JsonResponse
    {
        $totals = Sale::where('branch_id', $branchId)
            ->selectRaw('
                COUNT(*) as total_sales,
                SUM(final_price) as total_amount,
                AVG(final_price) as average_sale
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $totals
        ]);
    }

    public function getTotalsByDate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'branch_id' => 'sometimes|integer',
        ]);

        $query = Sale::whereBetween('sale_date', [$validated['date_from'], $validated['date_to']]);

        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        $totals = $query->selectRaw('
            COUNT(*) as total_sales,
            SUM(final_price) as total_amount,
            AVG(final_price) as average_sale,
            DATE(sale_date) as date
        ')
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $totals
        ]);
    }
}
