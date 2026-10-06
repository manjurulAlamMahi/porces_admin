<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeachService extends Model
{
    use HasFactory;

    protected $fillable = [
        'beach_id',
        'name',
        'price',
        'image',
        'is_active',
    ];

    public function beach()
    {
        return $this->belongsTo(Beach::class);
    }
}
