<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
     protected $fillable = [
        'town',
        'state',
    ];
    public function location()
    {
        return $this->belongsTo(Property::class);
    }
}
