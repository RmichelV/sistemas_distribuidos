<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Sucursal Principal', 'address' => 'Av. Principal 123'],
            ['name' => 'Sucursal Norte', 'address' => 'Calle Norte 456'],
            ['name' => 'Sucursal Sur', 'address' => 'Av. Sur 789'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
