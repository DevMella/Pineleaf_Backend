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
        'meta',
        'installment_count',
        'client_name',
        'parent_transaction_id',
    ];
    public function payment()
    {
        return $this->hasOne(Payment::class);
        return $this->hasOne(Installment::class);
        return $this->hasOne(Withdraw::class);
    }
    public function user()
{
    return $this->belongsTo(User::class);
}
    
}
