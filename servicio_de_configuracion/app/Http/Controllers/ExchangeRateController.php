<?php

namespace App\Http\Controllers;

use App\Models\UsdExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExchangeRateController extends Controller
{
    // Listar tasas de cambio
    public function index(Request $request)
    {
        $query = UsdExchangeRate::query();

        // Filtrar solo activas
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        // Filtrar por rango de fechas
        if ($request->has('from_date')) {
            $query->where('effective_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('effective_date', '<=', $request->to_date);
        }

        $rates = $query->orderBy('effective_date', 'desc')->paginate(15);

        return response()->json($rates);
    }

    // Obtener tasa actual
    public function current()
    {
        $rate = UsdExchangeRate::active()
            ->where('effective_date', '<=', now())
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$rate) {
            return response()->json([
                'message' => 'No hay tasa de cambio activa'
            ], 404);
        }

        return response()->json($rate);
    }

    // Obtener tasa en fecha específica
    public function getByDate($date)
    {
        $rate = UsdExchangeRate::effectiveOn($date)->first();

        if (!$rate) {
            return response()->json([
                'message' => 'No hay tasa de cambio para la fecha especificada'
            ], 404);
        }

        return response()->json($rate);
    }

    // Ver detalles de una tasa
    public function show($id)
    {
        $rate = UsdExchangeRate::find($id);

        if (!$rate) {
            return response()->json(['message' => 'Tasa no encontrada'], 404);
        }

        return response()->json($rate);
    }

    // Crear nueva tasa
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exchange_rate' => 'required|numeric|min:0.01|max:999999.99',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Desactivar tasas anteriores si esta es para hoy o futuro
        if ($request->effective_date >= now()->format('Y-m-d')) {
            UsdExchangeRate::where('is_active', true)->update(['is_active' => false]);
        }

        $rate = UsdExchangeRate::create([
            'exchange_rate' => $request->exchange_rate,
            'effective_date' => $request->effective_date,
            'notes' => $request->notes,
            'is_active' => true
        ]);

        return response()->json($rate, 201);
    }

    // Actualizar tasa
    public function update(Request $request, $id)
    {
        $rate = UsdExchangeRate::find($id);

        if (!$rate) {
            return response()->json(['message' => 'Tasa no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'exchange_rate' => 'sometimes|numeric|min:0.01|max:999999.99',
            'effective_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rate->update($request->only(['exchange_rate', 'effective_date', 'is_active', 'notes']));

        return response()->json($rate);
    }

    // Desactivar tasa
    public function deactivate($id)
    {
        $rate = UsdExchangeRate::find($id);

        if (!$rate) {
            return response()->json(['message' => 'Tasa no encontrada'], 404);
        }

        $rate->update(['is_active' => false]);

        return response()->json($rate);
    }

    // Eliminar tasa
    public function destroy($id)
    {
        $rate = UsdExchangeRate::find($id);

        if (!$rate) {
            return response()->json(['message' => 'Tasa no encontrada'], 404);
        }

        $rate->delete();

        return response()->json(['message' => 'Tasa eliminada correctamente']);
    }

    // Historial de tasas
    public function history(Request $request)
    {
        $months = $request->input('months', 12);
        
        $rates = UsdExchangeRate::where('effective_date', '>=', now()->subMonths($months))
            ->orderBy('effective_date', 'desc')
            ->get();

        return response()->json($rates);
    }
}
