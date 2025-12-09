<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean'
    ];

    // Accessor para obtener el valor con el tipo correcto
    public function getTypedValueAttribute()
    {
        return match($this->type) {
            'number' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value
        };
    }

    // Mutator para guardar el valor
    public function setValueAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['value'] = json_encode($value);
            $this->attributes['type'] = 'json';
        } elseif (is_bool($value)) {
            $this->attributes['value'] = $value ? '1' : '0';
            $this->attributes['type'] = 'boolean';
        } elseif (is_numeric($value)) {
            $this->attributes['value'] = (string) $value;
            $this->attributes['type'] = 'number';
        } else {
            $this->attributes['value'] = $value;
            $this->attributes['type'] = 'string';
        }
    }

    // Scope para configuraciones públicas
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Scope por categoría
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
