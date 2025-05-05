<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['user_id', 'payment_type','amount', 'gateway_ref','ref_no'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
