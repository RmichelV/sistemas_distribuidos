<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UsdExchangeRate;
use Carbon\Carbon;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            [
                'exchange_rate' => 6.96,
                'effective_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'is_active' => false,
                'notes' => 'Tasa histórica - Junio 2024'
            ],
            [
                'exchange_rate' => 6.97,
                'effective_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
                'is_active' => false,
                'notes' => 'Tasa histórica - Julio 2024'
            ],
            [
                'exchange_rate' => 6.96,
                'effective_date' => Carbon::now()->subMonths(4)->format('Y-m-d'),
                'is_active' => false,
                'notes' => 'Tasa histórica - Agosto 2024'
            ],
            [
                'exchange_rate' => 6.97,
                'effective_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
                'is_active' => false,
                'notes' => 'Tasa histórica - Septiembre 2024'
            ],
            [
                'exchange_rate' => 6.97,
                'effective_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'is_active' => false,
                'notes' => 'Tasa histórica - Octubre 2024'
            ],
            [
                'exchange_rate' => 6.96,
                'effective_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'is_active' => false,
                'notes' => 'Tasa histórica - Noviembre 2024'
            ],
            [
                'exchange_rate' => 6.97,
                'effective_date' => Carbon::now()->format('Y-m-d'),
                'is_active' => true,
                'notes' => 'Tasa actual vigente'
            ]
        ];

        foreach ($rates as $rate) {
            UsdExchangeRate::create($rate);
        }
    }
}
