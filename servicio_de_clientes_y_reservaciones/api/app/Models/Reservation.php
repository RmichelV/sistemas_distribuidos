<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'total_amount',
        'advance_amount',
        'rest_amount',
        'exchange_rate',
        'pay_type',
        'branch_id',
        'status',
        'reservation_date',
        'pickup_date'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'rest_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'reservation_date' => 'date',
        'pickup_date' => 'date'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(ReservationItem::class);
    }
}
