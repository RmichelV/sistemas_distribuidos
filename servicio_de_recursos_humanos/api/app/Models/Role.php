<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = "roles";
    protected $primaryKey = "id";
    
    protected $fillable = [
        'name'
    ];

    /**
     * Relación: Un rol tiene muchos usuarios
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
