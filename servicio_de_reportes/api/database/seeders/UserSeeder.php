<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener rol de Administrador
        $adminRole = Role::where('name', 'Administrador')->first();

        if (!$adminRole) {
            $this->command->error('El rol Administrador no existe. Ejecuta RoleSeeder primero.');
            return;
        }

        // Crear usuario administrador de prueba
        User::firstOrCreate(
            ['email' => 'admin@ewtto.com'],
            [
                'name' => 'Administrador',
                'address' => 'Oficina Principal',
                'phone' => '12345678',
                'role_id' => $adminRole->id,
                'branch_id' => 1, // Asume que existe sucursal con ID 1
                'base_salary' => 5000,
                'hire_date' => now()->subYears(2),
                'password' => Hash::make('admin123'),
            ]
        );

        $this->command->info('Usuario administrador creado: admin@ewtto.com / admin123');
    }
}
