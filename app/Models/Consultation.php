<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'consultation_date',
        'consultation_time',
        'notes',
        'no_attendees',
        'status',
        'mode'
    ];

    protected $casts = [
        'consultation_date' => 'date',
    ];

    public function property()
    {
    }
}