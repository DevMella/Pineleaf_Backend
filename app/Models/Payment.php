<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['user_id', 'ref_no', 'transaction_id'];

    public function user()
    {
        return $this->belongsTo(Transaction::class);
        return $this->belongsTo(Installment::class);
    }
}
