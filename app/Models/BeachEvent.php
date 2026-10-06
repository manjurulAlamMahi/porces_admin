<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeachEvent extends Model
{
    protected $fillable = [
        'beach_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'image'
    ];

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }
}
