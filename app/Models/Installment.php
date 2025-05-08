<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{

    protected $fillable = [
        'user_id',
        'transaction_id',
        'start_date',
        'end_date',
        'installment_count',
        'paid_count',
    ];
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
