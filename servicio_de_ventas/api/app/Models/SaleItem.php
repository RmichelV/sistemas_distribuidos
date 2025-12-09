<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity_products',
        'unit_price',
        'total_price',
        'exchange_rate'
    ];

    protected $casts = [
        'quantity_products' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }
}
