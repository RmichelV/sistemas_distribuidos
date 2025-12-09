<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador'],
            ['name' => 'Gerente'],
            ['name' => 'Vendedor'],
            ['name' => 'Cajero'],
            ['name' => 'Almacenero'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']]);
        }

        $this->command->info('Roles creados exitosamente');
    }
}
