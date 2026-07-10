# Datei: app\Models\SparePart.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\SparePart.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_ref',
        'label',
        'description',
        'category_id',
        'spare_part_type',
        'manufacturer',
        'supplier',
        'supplier_ref',
        'manufacturer_part_number',
        'storage_location_1',
        'storage_location_2',
        'purchase_price',
        'sales_price',
        'vat_rate',
        'unit',
        'stock_quantity',
        'minimum_stock',
        'active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'stock_quantity' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SparePartCategory::class, 'category_id');
    }

    public function eans(): HasMany
    {
        return $this->hasMany(SparePartEan::class);
    }

    public function compatibilities(): HasMany
    {
        return $this->hasMany(MachineSparePartCompatibility::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(SparePartStockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeCompatibleWith(Builder $query, int $machineProductId): Builder
    {
        return $query->whereHas('compatibilities', function (Builder $compatibility) use ($machineProductId): void {
            $compatibility->where('machine_product_id', $machineProductId);
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $search) use ($term): void {
            $search->where('part_ref', 'like', '%'.$term.'%')
                ->orWhere('label', 'like', '%'.$term.'%')
                ->orWhere('manufacturer', 'like', '%'.$term.'%')
                ->orWhere('spare_part_type', 'like', '%'.$term.'%')
                ->orWhere('supplier_ref', 'like', '%'.$term.'%')
                ->orWhere('manufacturer_part_number', 'like', '%'.$term.'%')
                ->orWhereHas('category', function (Builder $category) use ($term): void {
                    $category->where('name', 'like', '%'.$term.'%');
                })
                ->orWhereHas('eans', function (Builder $ean) use ($term): void {
                    $ean->where('ean', 'like', '%'.$term.'%');
                })
                ->orWhere('storage_location_1', 'like', '%'.$term.'%')
                ->orWhere('storage_location_2', 'like', '%'.$term.'%');
        });
    }

    public function adjustStock(float $quantity, string $type, ?Ticket $ticket = null, ?string $note = null, ?string $code = null): SparePartStockMovement
    {
        return DB::transaction(function () use ($quantity, $type, $ticket, $note, $code): SparePartStockMovement {
            $part = self::query()->lockForUpdate()->findOrFail($this->id);
            $before = (float) $part->stock_quantity;
            $after = $before + $quantity;

            $part->forceFill(['stock_quantity' => $after])->save();

            $movement = SparePartStockMovement::query()->create([
                'spare_part_id' => $part->id,
                'ticket_id' => $ticket?->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'code_snapshot' => $code,
                'note' => $note,
            ]);

            $this->forceFill(['stock_quantity' => $after]);

            return $movement;
        });
    }


    public function eanList(): string
    {
        return $this->eans->pluck('ean')->implode(', ');
    }

    public function stockLabel(): string
    {
        return number_format((float) $this->stock_quantity, 2, ',', '.').' '.$this->unit;
    }
}

```
