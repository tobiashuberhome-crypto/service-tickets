<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_ref',
        'machine_product_id',
        'title',
        'url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
