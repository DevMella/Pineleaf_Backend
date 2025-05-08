<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    protected $fillable = [
        'transaction_id',
        'bank_name',
        'account_name',
        'account_number',
        'status', 
        'created_at',
        'updated_at',
    ];
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
