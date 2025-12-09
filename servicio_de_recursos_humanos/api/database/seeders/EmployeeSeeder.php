<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::create([
            'name' => 'Roberto Méndez',
            'email' => 'roberto.mendez@ewtto.com',
            'position' => 'Gerente de Ventas',
            'branch_id' => 1,
            'base_salary' => 5000.00,
            'hire_date' => '2023-01-15',
            'status' => 'active',
            'phone' => '70111111',
            'notes' => 'Empleado del mes en Marzo 2024'
        ]);

        Employee::create([
            'name' => 'Sofía Ramírez',
            'email' => 'sofia.ramirez@ewtto.com',
            'position' => 'Vendedora',
            'branch_id' => 1,
            'base_salary' => 3000.00,
            'hire_date' => '2023-06-01',
            'status' => 'active',
            'phone' => '71222222'
        ]);

        Employee::create([
            'name' => 'Diego Castro',
            'email' => 'diego.castro@ewtto.com',
            'position' => 'Encargado de Inventario',
            'branch_id' => 2,
            'base_salary' => 3500.00,
            'hire_date' => '2023-03-20',
            'status' => 'active',
            'phone' => '72333333'
        ]);

        Employee::create([
            'name' => 'Laura Morales',
            'email' => 'laura.morales@ewtto.com',
            'position' => 'Cajera',
            'branch_id' => 2,
            'base_salary' => 2800.00,
            'hire_date' => '2024-01-10',
            'status' => 'active',
            'phone' => '73444444'
        ]);

        Employee::create([
            'name' => 'Fernando López',
            'email' => 'fernando.lopez@ewtto.com',
            'position' => 'Gerente de Sucursal',
            'branch_id' => 3,
            'base_salary' => 4500.00,
            'hire_date' => '2022-11-01',
            'status' => 'active',
            'phone' => '74555555',
            'notes' => '5 años de experiencia en retail'
        ]);
    }
}
