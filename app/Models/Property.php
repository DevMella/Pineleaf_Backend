<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'estate_name',
        'description',
        'images',
        'location',
        'landmark',
        'size',
        'land_condition',
        'document_title',
        'property_features',
        'type',
        'purpose',
        'price',
        'total_units',
        'flyer'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'images' => 'array',
        'landmark' => 'array',
        'property_features' => 'array',
        'price' => 'decimal:2',
        'total_units' => 'integer',
        'unit_sold' => 'integer',
    ];
}