<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeachTraffic extends Model
{
    protected $fillable = ['beach_id', 'level', 'people_count'];

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }
}
