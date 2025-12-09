<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devolution extends Model
{
    use HasFactory;

    protected $table = 'devolutions';

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'reason',
        'refund_amount'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'refund_amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }
}
