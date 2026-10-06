<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chair extends Model
{
    use HasFactory;

    protected $fillable = [
        'beach_id',
        'code',
        'price',
        'status',
        'x_coord',
        'y_coord',
    ];

    // Chair belongs to a beach
    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }

    // Chair can be attached to many bookings
    // public function bookingChairs()
    // {
    //     return $this->hasMany(BookingChair::class);
    // }
}
