<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineSparePartCompatibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_product_id',
        'machine_ref',
        'spare_part_id',
    ];

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }
}
