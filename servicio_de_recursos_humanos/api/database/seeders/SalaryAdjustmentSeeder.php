<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalaryAdjustment;
use App\Models\Employee;
use Carbon\Carbon;

class SalaryAdjustmentSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->count() == 0) {
            $this->command->warn('No hay empleados. Ejecuta EmployeeSeeder primero.');
            return;
        }

        // Bonos para empleado 1
        SalaryAdjustment::create([
            'employee_id' => 1,
            'adjustment_type' => 'bonus',
            'amount' => 500.00,
            'description' => 'Bono por cumplimiento de metas',
            'date' => Carbon::now()->subMonths(1)->toDateString(),
            'status' => 'paid'
        ]);

        // Horas extras para empleado 2
        SalaryAdjustment::create([
            'employee_id' => 2,
            'adjustment_type' => 'overtime',
            'amount' => 200.00,
            'description' => 'Horas extras - Black Friday',
            'date' => Carbon::now()->subDays(15)->toDateString(),
            'status' => 'approved'
        ]);

        // Aumento salarial para empleado 3
        SalaryAdjustment::create([
            'employee_id' => 3,
            'adjustment_type' => 'raise',
            'amount' => 300.00,
            'description' => 'Aumento anual por desempeño',
            'date' => Carbon::now()->subMonths(2)->toDateString(),
            'status' => 'approved'
        ]);

        // Descuento para empleado 4
        SalaryAdjustment::create([
            'employee_id' => 4,
            'adjustment_type' => 'deduction',
            'amount' => 150.00,
            'description' => 'Descuento por llegadas tarde',
            'date' => Carbon::now()->subDays(10)->toDateString(),
            'status' => 'pending'
        ]);

        // Bono pendiente para empleado 5
        SalaryAdjustment::create([
            'employee_id' => 5,
            'adjustment_type' => 'bonus',
            'amount' => 600.00,
            'description' => 'Bono por aniversario en la empresa',
            'date' => Carbon::now()->toDateString(),
            'status' => 'pending'
        ]);
    }
}
