<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SalaryAdjustmentController;

// Health check
Route::get("/health", function () {
    return response()->json([
        "service" => "hr-service",
        "status" => "healthy"
    ]);
});

// Employees
Route::prefix("employees")->group(function () {
    Route::get("/", [EmployeeController::class, "index"]);
    Route::post("/", [EmployeeController::class, "store"]);
    Route::get("/{id}", [EmployeeController::class, "show"]);
    Route::put("/{id}", [EmployeeController::class, "update"]);
    Route::delete("/{id}", [EmployeeController::class, "destroy"]);
    Route::get("/branch/{branchId}", [EmployeeController::class, "getByBranch"]);
    Route::get("/{id}/payroll", [EmployeeController::class, "getPayroll"]);
});

// Attendance
Route::prefix("attendance")->group(function () {
    Route::get("/", [AttendanceController::class, "index"]);
    Route::post("/", [AttendanceController::class, "store"]);
    Route::get("/{id}", [AttendanceController::class, "show"]);
    Route::put("/{id}", [AttendanceController::class, "update"]);
    Route::post("/check-in", [AttendanceController::class, "checkIn"]);
    Route::put("/{id}/check-out", [AttendanceController::class, "checkOut"]);
    Route::get("/employee/{employeeId}", [AttendanceController::class, "getByEmployee"]);
    Route::get("/employee/{employeeId}/summary", [AttendanceController::class, "getSummary"]);
});

// Salary Adjustments
Route::prefix("salary-adjustments")->group(function () {
    Route::get("/", [SalaryAdjustmentController::class, "index"]);
    Route::post("/", [SalaryAdjustmentController::class, "store"]);
    Route::get("/pending", [SalaryAdjustmentController::class, "getPendingApprovals"]);
    Route::get("/{id}", [SalaryAdjustmentController::class, "show"]);
    Route::put("/{id}", [SalaryAdjustmentController::class, "update"]);
    Route::delete("/{id}", [SalaryAdjustmentController::class, "destroy"]);
    Route::put("/{id}/approve", [SalaryAdjustmentController::class, "approve"]);
    Route::put("/{id}/mark-paid", [SalaryAdjustmentController::class, "markAsPaid"]);
    Route::get("/employee/{employeeId}", [SalaryAdjustmentController::class, "getByEmployee"]);
    Route::get("/employee/{employeeId}/totals", [SalaryAdjustmentController::class, "getTotalsByEmployee"]);
});
