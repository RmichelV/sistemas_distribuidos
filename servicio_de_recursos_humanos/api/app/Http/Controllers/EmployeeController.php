<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        // Filtro por sucursal
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filtro por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Búsqueda por nombre o email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($employees);
    }

    public function show($id)
    {
        $employee = Employee::with(['attendanceRecords', 'salaryAdjustments'])->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        return response()->json($employee);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'position' => 'required|string|max:255',
            'branch_id' => 'required|integer',
            'base_salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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

        $employee = Employee::create([
            'name' => $request->name,
            'email' => $request->email,
            'position' => $request->position,
            'branch_id' => $request->branch_id,
            'base_salary' => $request->base_salary,
            'hire_date' => $request->hire_date,
            'status' => 'active',
            'phone' => $request->phone,
            'notes' => $request->notes
        ]);

        return response()->json($employee, 201);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:employees,email,' . $id,
            'position' => 'sometimes|required|string|max:255',
            'branch_id' => 'sometimes|required|integer',
            'base_salary' => 'sometimes|required|numeric|min:0',
            'hire_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:active,inactive,suspended',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Si se actualiza la sucursal, validarla
        if ($request->has('branch_id') && $request->branch_id != $employee->branch_id) {
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
        }

        $employee->update($request->all());

        return response()->json($employee);
    }

    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        // No eliminar, solo cambiar estado
        $employee->update(['status' => 'inactive']);

        return response()->json(['message' => 'Empleado marcado como inactivo']);
    }

    public function getByBranch($branchId)
    {
        $employees = Employee::where('branch_id', $branchId)
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($employees);
    }

    public function getPayroll($employeeId)
    {
        $employee = Employee::with(['salaryAdjustments' => function($query) {
            $query->where('status', 'approved')
                  ->orderBy('date', 'desc');
        }])->find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $totalAdjustments = $employee->salaryAdjustments->sum(function($adj) {
            return $adj->adjustment_type === 'bonus' || $adj->adjustment_type === 'raise' || $adj->adjustment_type === 'overtime'
                ? $adj->amount
                : -$adj->amount;
        });

        return response()->json([
            'employee' => $employee,
            'base_salary' => $employee->base_salary,
            'total_adjustments' => $totalAdjustments,
            'net_salary' => $employee->base_salary + $totalAdjustments
        ]);
    }
}
