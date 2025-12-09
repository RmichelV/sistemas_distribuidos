<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'notes',
        'last_update'
    ];

    protected $casts = [
        'last_update' => 'date'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
