<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'property_id',
        'inspection_date',
        'inspection_time',
        'notes',
        'no_attendees',
        'status'
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}