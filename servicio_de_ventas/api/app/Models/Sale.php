<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $table = 'sales';

    protected $fillable = [
        'sale_code',
        'customer_name',
        'sale_date',
        'pay_type',
        'final_price',
        'exchange_rate',
        'notes',
        'branch_id'
    ];

    protected $casts = [
        'sale_date' => 'date',
        'final_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'id');
    }

    public function devolutions()
    {
        return $this->hasMany(Devolution::class, 'sale_id', 'id');
    }
}
