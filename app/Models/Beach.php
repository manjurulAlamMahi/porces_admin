<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Beach extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'country',
        'state',
        'city',
        'latitude',
        'longitude',
        'timezone',
    ];

    public function chairs()
    {
        return $this->hasMany(Chair::class);
    }

    public function beachServices()
    {
        return $this->hasMany(BeachService::class);
    }

    // // Beach bookings
    // public function bookings()
    // {
    //     return $this->hasMany(Booking::class);
    // }

    // // Beach shops (providers add shops under beaches)
    // public function shops()
    // {
    //     return $this->hasMany(Shop::class);
    // }
}
