<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_ref',
        'label',
        'quantity',
        'active',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'active' => 'boolean',
    ];
}
