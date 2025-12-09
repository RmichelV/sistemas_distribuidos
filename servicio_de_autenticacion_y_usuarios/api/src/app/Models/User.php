<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'branch_id',
        'address', 'phone', 'base_salary', 'hire_date'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];
}
