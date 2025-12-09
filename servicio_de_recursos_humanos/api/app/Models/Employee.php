<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'position',
        'branch_id',
        'base_salary',
        'hire_date',
        'status',
        'phone',
        'notes'
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'hire_date' => 'date'
    ];

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function salaryAdjustments()
    {
        return $this->hasMany(SalaryAdjustment::class);
    }
}
