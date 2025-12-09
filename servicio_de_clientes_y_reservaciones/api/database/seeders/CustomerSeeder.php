<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::create([
            'name' => 'Juan Pérez',
            'address' => 'Av. Principal #123, La Paz',
            'phone' => '70123456',
            'email' => 'juan.perez@email.com',
            'notes' => 'Cliente frecuente',
            'last_update' => now()
        ]);

        Customer::create([
            'name' => 'María González',
            'address' => 'Calle Comercio #456, La Paz',
            'phone' => '71234567',
            'email' => 'maria.gonzalez@email.com',
            'notes' => null,
            'last_update' => now()
        ]);

        Customer::create([
            'name' => 'Carlos Rodríguez',
            'address' => 'Zona Sur #789, La Paz',
            'phone' => '72345678',
            'email' => 'carlos.rodriguez@email.com',
            'notes' => 'Prefiere pago en efectivo',
            'last_update' => now()
        ]);

        Customer::create([
            'name' => 'Ana Martínez',
            'address' => 'Av. 6 de Agosto #321, La Paz',
            'phone' => '73456789',
            'email' => 'ana.martinez@email.com',
            'notes' => null,
            'last_update' => now()
        ]);

        Customer::create([
            'name' => 'Luis Fernández',
            'address' => 'Calacoto #654, La Paz',
            'phone' => '74567890',
            'email' => 'luis.fernandez@email.com',
            'notes' => 'Cliente corporativo',
            'last_update' => now()
        ]);
    }
}
