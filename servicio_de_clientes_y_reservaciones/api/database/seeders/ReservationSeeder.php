<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Customer;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->count() == 0) {
            $this->command->warn('No hay clientes. Ejecuta CustomerSeeder primero.');
            return;
        }

        // Reserva 1: Cliente 1, Branch 1
        $reservation1 = Reservation::create([
            'customer_id' => $customers[0]->id,
            'branch_id' => 1,
            'total_amount' => 850.00,
            'advance_amount' => 400.00,
            'rest_amount' => 450.00,
            'exchange_rate' => 6.96,
            'pay_type' => 'efectivo',
            'status' => 'pending',
            'reservation_date' => now(),
            'pickup_date' => now()->addDays(3)
        ]);

        ReservationItem::create([
            'reservation_id' => $reservation1->id,
            'product_id' => 1,
            'quantity_products' => 2,
            'unit_price' => 250.00,
            'total_price' => 500.00,
            'exchange_rate' => 6.96
        ]);

        ReservationItem::create([
            'reservation_id' => $reservation1->id,
            'product_id' => 2,
            'quantity_products' => 1,
            'unit_price' => 350.00,
            'total_price' => 350.00,
            'exchange_rate' => 6.96
        ]);

        // Reserva 2: Cliente 2, Branch 2
        $reservation2 = Reservation::create([
            'customer_id' => $customers[1]->id,
            'branch_id' => 2,
            'total_amount' => 1200.00,
            'advance_amount' => 1200.00,
            'rest_amount' => 0.00,
            'exchange_rate' => 6.96,
            'pay_type' => 'tarjeta',
            'status' => 'confirmed',
            'reservation_date' => now()->subDays(2),
            'pickup_date' => now()->addDays(1)
        ]);

        ReservationItem::create([
            'reservation_id' => $reservation2->id,
            'product_id' => 3,
            'quantity_products' => 3,
            'unit_price' => 400.00,
            'total_price' => 1200.00,
            'exchange_rate' => 6.96
        ]);

        // Reserva 3: Cliente 3, Branch 1
        $reservation3 = Reservation::create([
            'customer_id' => $customers[2]->id,
            'branch_id' => 1,
            'total_amount' => 650.00,
            'advance_amount' => 200.00,
            'rest_amount' => 450.00,
            'exchange_rate' => 6.96,
            'pay_type' => 'transferencia',
            'status' => 'pending',
            'reservation_date' => now()->subDays(1),
            'pickup_date' => now()->addDays(5)
        ]);

        ReservationItem::create([
            'reservation_id' => $reservation3->id,
            'product_id' => 4,
            'quantity_products' => 1,
            'unit_price' => 450.00,
            'total_price' => 450.00,
            'exchange_rate' => 6.96
        ]);

        ReservationItem::create([
            'reservation_id' => $reservation3->id,
            'product_id' => 5,
            'quantity_products' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00,
            'exchange_rate' => 6.96
        ]);
    }
}
