<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    protected $fillable = [
        'beach_id',
        'condition',
        'temperature',
        'wind_speed',
        'humidity'
    ];

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }
}
