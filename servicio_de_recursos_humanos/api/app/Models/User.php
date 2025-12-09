<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = "users";
    protected $primaryKey = "id";

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'address',
        'phone',
        'branch_id',  // ID de sucursal (validado en branch-service)
        'role_id',
        'base_salary',
        'hire_date',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
            'base_salary' => 'integer',
        ];
    }

    /**
     * Relación: Un usuario pertenece a un rol
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * NOTA: branch_id NO tiene relación Eloquent porque branches
     * está en otro microservicio (branch-service).
     * 
     * Para obtener datos de la sucursal, usar HTTP request:
     * Http::get("http://branch_api/api/branches/{$user->branch_id}")
     */

    // ==========================================
    // JWT Methods (requeridos por JWTSubject)
    // ==========================================

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'role_id' => $this->role_id,
            'role_name' => $this->role?->name,
            'branch_id' => $this->branch_id,
        ];
    }
}
