<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsdExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_rate',
        'effective_date',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:2',
        'effective_date' => 'date',
        'is_active' => 'boolean'
    ];

    // Scope para obtener solo tasas activas
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para obtener tasa vigente en una fecha específica
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->where('is_active', true)
            ->orderBy('effective_date', 'desc');
    }
}
