<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TideSchedule extends Model
{
    protected $fillable = [
        'beach_id',
        'date',
        'high_tide_time',
        'low_tide_time'
    ];

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }
}
