<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->count() == 0) {
            $this->command->warn('No hay empleados. Ejecuta EmployeeSeeder primero.');
            return;
        }

        // Registros de los últimos 5 días
        for ($i = 4; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            foreach ($employees as $employee) {
                // Simular algunos días de ausencia/vacaciones
                if ($i == 3 && $employee->id == 2) {
                    AttendanceRecord::create([
                        'employee_id' => $employee->id,
                        'attendance_status' => 'sick_leave',
                        'attendance_date' => $date,
                        'notes' => 'Permiso médico'
                    ]);
                    continue;
                }

                if ($i == 2 && $employee->id == 4) {
                    AttendanceRecord::create([
                        'employee_id' => $employee->id,
                        'attendance_status' => 'vacation',
                        'attendance_date' => $date,
                        'notes' => 'Vacaciones programadas'
                    ]);
                    continue;
                }

                // Simular llegadas tarde ocasionales
                $status = ($i == 1 && $employee->id % 2 == 0) ? 'late' : 'present';
                $checkIn = $status === 'late' ? '09:15' : '08:00';

                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'attendance_status' => $status,
                    'attendance_date' => $date,
                    'check_in_at' => $checkIn,
                    'check_out_at' => '17:00',
                    'minutes_worked' => $status === 'late' ? 465 : 540 // 8 horas o 7:45
                ]);
            }
        }
    }
}
