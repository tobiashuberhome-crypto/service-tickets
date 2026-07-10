<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerMachine extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_room_id',
        'dolibarr_customer_id',
        'customer_name_snapshot',
        'dolibarr_machine_product_id',
        'manufacturer_snapshot',
        'machine_ref_snapshot',
        'serial_number',
        'qr_token',
    ];

    public function schoolRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolRoom::class, 'school_room_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function displayName(): string
    {
        $manufacturer = $this->manufacturer_snapshot ? $this->manufacturer_snapshot.' / ' : '';
        $serial = $this->serial_number ? ' Â· SN '.$this->serial_number : '';

        return $manufacturer.$this->machine_ref_snapshot.$serial;
    }
}
