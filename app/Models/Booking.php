<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'beach_id',
        'booking_date',
        'status',
        'subtotal',
        'total'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }

    public function chairs()
    {
        return $this->hasMany(BookingChair::class);
    }

    public function services()
    {
        return $this->hasMany(BookingService::class);
    }
}
