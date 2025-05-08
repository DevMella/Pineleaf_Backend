<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'transaction_type',
        'ref_no',
        'property_purchased_id',
        'proof_of_payment',
        'units',
        'status',
    ];
    public function payment()
    {
        return $this->hasOne(Payment::class);
        return $this->hasOne(Installment::class);
    }
    
}
