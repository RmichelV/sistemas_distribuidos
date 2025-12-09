<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::with('employee');

        // Filtro por empleado
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filtro por fecha
        if ($request->has('date')) {
            $query->whereDate('attendance_date', $request->date);
        }

        // Filtro por rango de fechas
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('attendance_date', [$request->date_from, $request->date_to]);
        }

        // Filtro por estado
        if ($request->has('status')) {
            $query->where('attendance_status', $request->status);
        }

        $records = $query->orderBy('attendance_date', 'desc')->paginate(15);

        return response()->json($records);
    }

    public function show($id)
    {
        $record = AttendanceRecord::with('employee')->find($id);

        if (!$record) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        return response()->json($record);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'attendance_status' => 'required|in:present,absent,late,sick_leave,vacation',
            'attendance_date' => 'required|date',
            'check_in_at' => 'nullable|date_format:H:i',
            'check_out_at' => 'nullable|date_format:H:i|after:check_in_at',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validar empleado existe
        $employee = Employee::find($request->employee_id);
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        // Validar no duplicar registro para misma fecha
        $exists = AttendanceRecord::where('employee_id', $request->employee_id)
            ->whereDate('attendance_date', $request->attendance_date)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un registro de asistencia para este empleado en esta fecha'
            ], 422);
        }

        // Calcular minutos trabajados si hay check in/out
        $minutesWorked = null;
        if ($request->check_in_at && $request->check_out_at) {
            $checkIn = Carbon::createFromFormat('H:i', $request->check_in_at);
            $checkOut = Carbon::createFromFormat('H:i', $request->check_out_at);
            $minutesWorked = $checkOut->diffInMinutes($checkIn);
        }

        $record = AttendanceRecord::create([
            'employee_id' => $request->employee_id,
            'attendance_status' => $request->attendance_status,
            'attendance_date' => $request->attendance_date,
            'check_in_at' => $request->check_in_at,
            'check_out_at' => $request->check_out_at,
            'minutes_worked' => $minutesWorked,
            'notes' => $request->notes
        ]);

        return response()->json($record, 201);
    }

    public function update(Request $request, $id)
    {
        $record = AttendanceRecord::find($id);

        if (!$record) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'attendance_status' => 'sometimes|required|in:present,absent,late,sick_leave,vacation',
            'check_in_at' => 'nullable|date_format:H:i',
            'check_out_at' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Recalcular minutos si se actualizan los tiempos
        if ($request->has('check_in_at') || $request->has('check_out_at')) {
            $checkInTime = $request->check_in_at ?? $record->check_in_at;
            $checkOutTime = $request->check_out_at ?? $record->check_out_at;

            if ($checkInTime && $checkOutTime) {
                $checkIn = Carbon::createFromFormat('H:i', $checkInTime);
                $checkOut = Carbon::createFromFormat('H:i', $checkOutTime);
                $request->merge(['minutes_worked' => $checkOut->diffInMinutes($checkIn)]);
            }
        }

        $record->update($request->all());

        return response()->json($record);
    }

    public function getByEmployee($employeeId)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $records = AttendanceRecord::where('employee_id', $employeeId)
            ->orderBy('attendance_date', 'desc')
            ->get();

        return response()->json($records);
    }

    public function getSummary($employeeId, Request $request)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $query = AttendanceRecord::where('employee_id', $employeeId);

        // Filtro por mes/año
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('attendance_date', $request->month)
                  ->whereYear('attendance_date', $request->year);
        }

        $records = $query->get();

        $summary = [
            'employee' => $employee,
            'total_days' => $records->count(),
            'present' => $records->where('attendance_status', 'present')->count(),
            'absent' => $records->where('attendance_status', 'absent')->count(),
            'late' => $records->where('attendance_status', 'late')->count(),
            'sick_leave' => $records->where('attendance_status', 'sick_leave')->count(),
            'vacation' => $records->where('attendance_status', 'vacation')->count(),
            'total_hours_worked' => round($records->sum('minutes_worked') / 60, 2)
        ];

        return response()->json($summary);
    }

    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'check_in_at' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::find($request->employee_id);
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $today = now()->toDateString();
        
        $record = AttendanceRecord::where('employee_id', $request->employee_id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($record) {
            return response()->json([
                'message' => 'Ya existe un registro de entrada para hoy'
            ], 422);
        }

        $record = AttendanceRecord::create([
            'employee_id' => $request->employee_id,
            'attendance_status' => 'present',
            'attendance_date' => $today,
            'check_in_at' => $request->check_in_at
        ]);

        return response()->json($record, 201);
    }

    public function checkOut(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'check_out_at' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $record = AttendanceRecord::find($id);

        if (!$record) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        if ($record->check_out_at) {
            return response()->json([
                'message' => 'Ya se registró la salida para este día'
            ], 422);
        }

        if (!$record->check_in_at) {
            return response()->json([
                'message' => 'No hay registro de entrada para registrar salida'
            ], 422);
        }

        $checkIn = Carbon::createFromFormat('H:i', $record->check_in_at);
        $checkOut = Carbon::createFromFormat('H:i', $request->check_out_at);
        $minutesWorked = $checkOut->diffInMinutes($checkIn);

        $record->update([
            'check_out_at' => $request->check_out_at,
            'minutes_worked' => $minutesWorked
        ]);

        return response()->json($record);
    }
}
