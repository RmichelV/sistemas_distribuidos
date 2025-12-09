<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBranch extends Model
{
    use HasFactory;
    
    protected $table = 'product_branches';
    
    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity_in_stock',
        'unit_price',
        'units_per_box',
        'last_update',
    ];

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'unit_price' => 'decimal:2',
        'units_per_box' => 'integer',
        'last_update' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
