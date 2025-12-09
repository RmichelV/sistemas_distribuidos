<?php

namespace App\Http\Controllers;

use App\Models\SalaryAdjustment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalaryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryAdjustment::with('employee');

        // Filtro por empleado
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filtro por tipo
        if ($request->has('adjustment_type')) {
            $query->where('adjustment_type', $request->adjustment_type);
        }

        // Filtro por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por rango de fechas
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        $adjustments = $query->orderBy('date', 'desc')->paginate(15);

        return response()->json($adjustments);
    }

    public function show($id)
    {
        $adjustment = SalaryAdjustment::with('employee')->find($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Ajuste no encontrado'], 404);
        }

        return response()->json($adjustment);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'adjustment_type' => 'required|in:bonus,deduction,raise,overtime',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validar empleado existe
        $employee = Employee::find($request->employee_id);
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $adjustment = SalaryAdjustment::create([
            'employee_id' => $request->employee_id,
            'adjustment_type' => $request->adjustment_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'status' => 'pending'
        ]);

        return response()->json($adjustment, 201);
    }

    public function update(Request $request, $id)
    {
        $adjustment = SalaryAdjustment::find($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Ajuste no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'adjustment_type' => 'sometimes|required|in:bonus,deduction,raise,overtime',
            'amount' => 'sometimes|required|numeric|min:0',
            'description' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:pending,approved,paid'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $adjustment->update($request->all());

        return response()->json($adjustment);
    }

    public function destroy($id)
    {
        $adjustment = SalaryAdjustment::find($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Ajuste no encontrado'], 404);
        }

        if ($adjustment->status === 'paid') {
            return response()->json([
                'message' => 'No se puede eliminar un ajuste ya pagado'
            ], 422);
        }

        $adjustment->delete();

        return response()->json(['message' => 'Ajuste eliminado exitosamente']);
    }

    public function approve($id)
    {
        $adjustment = SalaryAdjustment::find($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Ajuste no encontrado'], 404);
        }

        if ($adjustment->status !== 'pending') {
            return response()->json([
                'message' => 'Solo se pueden aprobar ajustes pendientes'
            ], 422);
        }

        $adjustment->update(['status' => 'approved']);

        return response()->json($adjustment);
    }

    public function markAsPaid($id)
    {
        $adjustment = SalaryAdjustment::find($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Ajuste no encontrado'], 404);
        }

        if ($adjustment->status !== 'approved') {
            return response()->json([
                'message' => 'Solo se pueden marcar como pagados los ajustes aprobados'
            ], 422);
        }

        $adjustment->update(['status' => 'paid']);

        return response()->json($adjustment);
    }

    public function getByEmployee($employeeId)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $adjustments = SalaryAdjustment::where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($adjustments);
    }

    public function getPendingApprovals()
    {
        $adjustments = SalaryAdjustment::with('employee')
            ->where('status', 'pending')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($adjustments);
    }

    public function getTotalsByEmployee($employeeId)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $adjustments = SalaryAdjustment::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->get();

        $totals = [
            'employee' => $employee,
            'total_bonuses' => $adjustments->where('adjustment_type', 'bonus')->sum('amount'),
            'total_deductions' => $adjustments->where('adjustment_type', 'deduction')->sum('amount'),
            'total_raises' => $adjustments->where('adjustment_type', 'raise')->sum('amount'),
            'total_overtime' => $adjustments->where('adjustment_type', 'overtime')->sum('amount'),
            'net_adjustment' => $adjustments->sum(function($adj) {
                return $adj->adjustment_type === 'deduction' ? -$adj->amount : $adj->amount;
            })
        ];

        return response()->json($totals);
    }
}
