<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'adjustment_type',
        'amount',
        'description',
        'date',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
