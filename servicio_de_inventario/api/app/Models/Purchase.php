<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;
    
    protected $table = 'purchases';
    
    protected $fillable = [
        'product_id',
        'purchase_quantity',
        'purchase_date',
        'branch_id',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'purchase_quantity' => 'integer',
        'purchase_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
