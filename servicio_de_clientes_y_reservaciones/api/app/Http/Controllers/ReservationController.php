<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['customer', 'items']);

        // Filtro por sucursal
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filtro por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por cliente
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filtro por rango de fechas
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('reservation_date', [$request->date_from, $request->date_to]);
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($reservations);
    }

    public function show($id)
    {
        $reservation = Reservation::with(['customer', 'items'])->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        return response()->json($reservation);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'pay_type' => 'required|in:efectivo,tarjeta,transferencia',
            'advance_amount' => 'required|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reservation_date' => 'required|date',
            'pickup_date' => 'nullable|date|after_or_equal:reservation_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity_products' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validar cliente existe
        $customer = Customer::find($request->customer_id);
        if (!$customer) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        // Validar sucursal existe (HTTP al servicio de sucursales)
        try {
            $branchResponse = Http::timeout(5)->get("http://branch_nginx/api/branches/{$request->branch_id}/exists");
            if (!$branchResponse->successful() || !$branchResponse->json('exists')) {
                return response()->json(['message' => 'Sucursal no válida'], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al validar sucursal',
                'error' => $e->getMessage()
            ], 500);
        }

        // Validar productos existen (HTTP al servicio de inventario)
        foreach ($request->items as $item) {
            try {
                $productResponse = Http::timeout(5)->get("http://inventory_nginx/api/products/{$item['product_id']}");
                if (!$productResponse->successful()) {
                    return response()->json([
                        'message' => "Producto {$item['product_id']} no encontrado"
                    ], 404);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al validar productos',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        DB::beginTransaction();

        try {
            // Calcular total
            $exchangeRate = $request->exchange_rate ?? 1.0;
            $totalAmount = 0;

            foreach ($request->items as $item) {
                $totalPrice = $item['quantity_products'] * $item['unit_price'];
                $totalAmount += $totalPrice;
            }

            $restAmount = $totalAmount - $request->advance_amount;

            // Crear reserva
            $reservation = Reservation::create([
                'customer_id' => $request->customer_id,
                'branch_id' => $request->branch_id,
                'total_amount' => $totalAmount,
                'advance_amount' => $request->advance_amount,
                'rest_amount' => $restAmount,
                'exchange_rate' => $exchangeRate,
                'pay_type' => $request->pay_type,
                'status' => 'pending',
                'reservation_date' => $request->reservation_date,
                'pickup_date' => $request->pickup_date
            ]);

            // Crear items
            foreach ($request->items as $item) {
                $totalPrice = $item['quantity_products'] * $item['unit_price'];
                
                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'product_id' => $item['product_id'],
                    'quantity_products' => $item['quantity_products'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'exchange_rate' => $exchangeRate
                ]);
            }

            // Actualizar last_update del cliente
            $customer->update(['last_update' => now()]);

            DB::commit();

            return response()->json(
                Reservation::with(['customer', 'items'])->find($reservation->id),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        $reservation->update(['status' => $request->status]);

        return response()->json($reservation);
    }

    public function updatePayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        $newAdvance = $reservation->advance_amount + $request->payment_amount;
        
        if ($newAdvance > $reservation->total_amount) {
            return response()->json([
                'message' => 'El pago excede el monto total de la reserva'
            ], 422);
        }

        $reservation->update([
            'advance_amount' => $newAdvance,
            'rest_amount' => $reservation->total_amount - $newAdvance
        ]);

        // Si se completó el pago, actualizar estado
        if ($reservation->rest_amount == 0) {
            $reservation->update(['status' => 'confirmed']);
        }

        return response()->json($reservation);
    }

    public function getByCustomer($customerId)
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $reservations = Reservation::with('items')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations);
    }

    public function getByBranch($branchId)
    {
        $reservations = Reservation::with(['customer', 'items'])
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations);
    }

    public function getTotalsByBranch($branchId)
    {
        $stats = Reservation::where('branch_id', $branchId)
            ->selectRaw('
                COUNT(*) as total_reservations,
                SUM(total_amount) as total_amount,
                SUM(advance_amount) as total_advance,
                SUM(rest_amount) as total_pending
            ')
            ->first();

        return response()->json($stats);
    }

    public function getPendingReservations()
    {
        $reservations = Reservation::with(['customer', 'items'])
            ->where('status', 'pending')
            ->where('rest_amount', '>', 0)
            ->orderBy('reservation_date', 'asc')
            ->get();

        return response()->json($reservations);
    }
}
