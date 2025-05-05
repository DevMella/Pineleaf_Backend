<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
        'unit_sold',
        'flyer',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
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