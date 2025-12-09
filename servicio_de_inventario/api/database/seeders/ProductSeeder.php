<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductStore;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Laptop Dell Inspiron 15',
                'code' => 'LAPTOP-DELL-001',
                'img_product' => 'laptop-dell.jpg',
                'store' => ['quantity' => 50, 'unit_price' => 450.00, 'price_multiplier' => 1.30]
            ],
            [
                'name' => 'Mouse Logitech MX Master 3',
                'code' => 'MOUSE-LOG-001',
                'img_product' => 'mouse-logitech.jpg',
                'store' => ['quantity' => 200, 'unit_price' => 35.00, 'price_multiplier' => 1.40]
            ],
            [
                'name' => 'Teclado Mecánico Corsair K95',
                'code' => 'KEYB-COR-001',
                'img_product' => 'keyboard-corsair.jpg',
                'store' => ['quantity' => 100, 'unit_price' => 120.00, 'price_multiplier' => 1.35]
            ],
            [
                'name' => 'Monitor LG UltraWide 34"',
                'code' => 'MON-LG-001',
                'img_product' => 'monitor-lg.jpg',
                'store' => ['quantity' => 30, 'unit_price' => 380.00, 'price_multiplier' => 1.25]
            ],
            [
                'name' => 'Webcam Logitech C920',
                'code' => 'WEBCAM-LOG-001',
                'img_product' => 'webcam-logitech.jpg',
                'store' => ['quantity' => 150, 'unit_price' => 60.00, 'price_multiplier' => 1.45]
            ],
            [
                'name' => 'Auriculares Sony WH-1000XM4',
                'code' => 'AUR-SONY-001',
                'img_product' => 'headphones-sony.jpg',
                'store' => ['quantity' => 80, 'unit_price' => 280.00, 'price_multiplier' => 1.30]
            ],
            [
                'name' => 'SSD Samsung 1TB',
                'code' => 'SSD-SAM-001',
                'img_product' => 'ssd-samsung.jpg',
                'store' => ['quantity' => 300, 'unit_price' => 85.00, 'price_multiplier' => 1.50]
            ],
            [
                'name' => 'Router TP-Link AX3000',
                'code' => 'ROUTER-TP-001',
                'img_product' => 'router-tplink.jpg',
                'store' => ['quantity' => 120, 'unit_price' => 95.00, 'price_multiplier' => 1.35]
            ]
        ];

        foreach ($products as $productData) {
            $storeData = $productData['store'];
            unset($productData['store']);

            $product = Product::create($productData);

            ProductStore::create([
                'product_id' => $product->id,
                'quantity' => $storeData['quantity'],
                'unit_price' => $storeData['unit_price'],
                'price_multiplier' => $storeData['price_multiplier'],
                'last_update' => now(),
                'branch_id' => null
            ]);
        }
    }
}
