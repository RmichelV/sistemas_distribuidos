<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_status',
        'attendance_date',
        'check_in_at',
        'check_out_at',
        'minutes_worked',
        'notes'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime:H:i',
        'check_out_at' => 'datetime:H:i'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
