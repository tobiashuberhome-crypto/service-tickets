<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineDocumentMachineRef extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_document_id',
        'machine_ref',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(MachineDocument::class, 'machine_document_id');
    }
}

