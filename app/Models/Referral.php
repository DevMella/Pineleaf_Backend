<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = ['referral_id', 'referee_id', 'level'];
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }

    // Optional relationship: Who was referred
    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }
    
}
