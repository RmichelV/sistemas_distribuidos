<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $table = 'products';
    
    protected $fillable = [
        'name',
        'code',
        'img_product',
    ];

    public function productStore()
    {
        return $this->hasOne(ProductStore::class);
    }

    public function productBranches()
    {
        return $this->hasMany(ProductBranch::class, 'product_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
