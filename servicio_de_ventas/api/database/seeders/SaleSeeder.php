<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleItem;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $sales = [
            [
                'sale_code' => 'V-20251209-000001',
                'customer_name' => 'Juan Pérez',
                'sale_date' => '2025-12-09',
                'pay_type' => 'efectivo',
                'exchange_rate' => 1.0000,
                'notes' => 'Venta de mostrador',
                'branch_id' => 1,
                'items' => [
                    ['product_id' => 1, 'quantity' => 2, 'unit_price' => 585.00], // Laptop con markup
                    ['product_id' => 2, 'quantity' => 1, 'unit_price' => 49.00],  // Mouse
                ]
            ],
            [
                'sale_code' => 'V-20251209-000002',
                'customer_name' => 'María González',
                'sale_date' => '2025-12-09',
                'pay_type' => 'tarjeta',
                'exchange_rate' => 1.0000,
                'notes' => 'Cliente frecuente',
                'branch_id' => 1,
                'items' => [
                    ['product_id' => 3, 'quantity' => 1, 'unit_price' => 162.00], // Teclado
                    ['product_id' => 4, 'quantity' => 1, 'unit_price' => 475.00], // Monitor
                ]
            ],
            [
                'sale_code' => 'V-20251209-000003',
                'customer_name' => 'Carlos Rodríguez',
                'sale_date' => '2025-12-08',
                'pay_type' => 'transferencia',
                'exchange_rate' => 1.0000,
                'notes' => null,
                'branch_id' => 2,
                'items' => [
                    ['product_id' => 5, 'quantity' => 3, 'unit_price' => 87.00],  // Webcam
                    ['product_id' => 6, 'quantity' => 1, 'unit_price' => 364.00], // Auriculares
                ]
            ],
        ];

        foreach ($sales as $saleData) {
            $items = $saleData['items'];
            unset($saleData['items']);

            // Calcular precio final
            $finalPrice = 0;
            foreach ($items as $item) {
                $finalPrice += $item['unit_price'] * $item['quantity'];
            }

            $saleData['final_price'] = $finalPrice;

            $sale = Sale::create($saleData);

            // Crear items
            foreach ($items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity_products' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['unit_price'] * $item['quantity'],
                    'exchange_rate' => $saleData['exchange_rate'],
                ]);
            }
        }
    }
}
