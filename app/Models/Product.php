<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'sku',
        'description',
        'price',
        'available_qty',
        'is_active',
        'is_famous',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
