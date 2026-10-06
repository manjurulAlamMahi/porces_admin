<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingChair extends Model
{
    protected $fillable = ['booking_id', 'chair_id', 'price'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function chair()
    {
        return $this->belongsTo(Chair::class);
    }
}
