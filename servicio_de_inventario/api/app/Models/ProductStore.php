<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStore extends Model
{
    use HasFactory;
    
    protected $table = 'product_stores';
    
    protected $fillable = [
        'product_id',
        'quantity',
        'unit_price',
        'price_multiplier',
        'last_update',
        'branch_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'price_multiplier' => 'decimal:4',
        'last_update' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
