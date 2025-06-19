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
        'property_purchased_id',
        'client_name',
        'amount',
    ];
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
    protected $table = 'installments';

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship to the Property model
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_purchased_id');
    }
}
