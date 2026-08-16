<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerMachineProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'dolibarr_customer_id',
        'serial_number',
        'contact_name',
        'email',
        'phone',
        'street',
        'zip',
        'city',
        'manufacturer_snapshot',
        'machine_ref_snapshot',
        'warranty_claimed',
        'accessory_presser_foot',
        'accessory_bobbin_case',
        'accessory_bobbin',
        'accessory_power_cable',
        'accessory_foot_pedal',
        'accessory_case',
        'accessory_other',
        'repair_approval_limit',
        'intake_note',
    ];

    protected $casts = [
        'warranty_claimed' => 'boolean',
        'accessory_presser_foot' => 'boolean',
        'accessory_bobbin_case' => 'boolean',
        'accessory_bobbin' => 'boolean',
        'accessory_power_cable' => 'boolean',
        'accessory_foot_pedal' => 'boolean',
        'accessory_case' => 'boolean',
        'repair_approval_limit' => 'decimal:2',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function accessoriesSummary(): string
    {
        $items = [];

        if ($this->accessory_presser_foot) {
            $items[] = 'Naehfuss';
        }
        if ($this->accessory_bobbin_case) {
            $items[] = 'Spulenkapsel';
        }
        if ($this->accessory_bobbin) {
            $items[] = 'Unterfadenspule';
        }
        if ($this->accessory_power_cable) {
            $items[] = 'Kabel';
        }
        if ($this->accessory_foot_pedal) {
            $items[] = 'Fussanlasser';
        }
        if ($this->accessory_case) {
            $items[] = 'Koffer';
        }
        if ($this->accessory_other) {
            $items[] = 'Sonstiges: '.$this->accessory_other;
        }

        return implode(', ', $items);
    }
}
