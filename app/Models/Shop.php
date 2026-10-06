<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'beach_id',
        'service_id',
        'name',
        'description',
        'opening_time',
        'closing_time',
        'is_active',
        'ratings',
        'image',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
