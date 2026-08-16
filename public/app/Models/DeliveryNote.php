<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'disk', 'path', 'created_by'];

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'delivery_note_ticket')->withTimestamps();
    }
}
