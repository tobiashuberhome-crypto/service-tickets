# Datei: resources\views\spare-parts\partials\form.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\spare-parts\partials\form.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
<div class="grid grid-2">
    <div class="stack">
        <div class="form-row">
            <div>
                <label for="part_ref">Artikelnummer</label>
                <input id="part_ref" name="part_ref" value="{{ old('part_ref', $sparePart->part_ref) }}" required>
            </div>
            <div>
                <label for="label">Bezeichnung</label>
                <input id="label" name="label" value="{{ old('label', $sparePart->label) }}" required>
            </div>
        </div>

        <div>
            <label for="description">Beschreibung</label>
            <textarea id="description" name="description">{{ old('description', $sparePart->description) }}</textarea>
        </div>

        <div class="form-row">
            <div>
                <label for="category_id">Kategorie</label>
                <select id="category_id" name="category_id">
                    <option value="">Keine Kategorie</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $sparePart->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="muted"><a href="{{ route('spare-part-categories.index') }}">Kategorien verwalten</a></p>
            </div>
            <div>
                <label for="spare_part_type">Ersatzteil-Typ</label>
                <input id="spare_part_type" name="spare_part_type" value="{{ old('spare_part_type', $sparePart->spare_part_type) }}">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="manufacturer">Hersteller</label>
                <input id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $sparePart->manufacturer) }}">
            </div>
            <div>
                <label for="unit">Einheit</label>
                <input id="unit" name="unit" value="{{ old('unit', $sparePart->unit ?: 'Stk') }}" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="storage_location_1">Lagerplatz 1</label>
                <input id="storage_location_1" name="storage_location_1" value="{{ old('storage_location_1', $sparePart->storage_location_1) }}">
            </div>
            <div>
                <label for="storage_location_2">Lagerplatz 2</label>
                <input id="storage_location_2" name="storage_location_2" value="{{ old('storage_location_2', $sparePart->storage_location_2) }}">
            </div>
        </div>

        <label class="check-row">
            <input type="checkbox" name="active" value="1" @checked(old('active', $sparePart->active ?? true))>
            Aktiv
        </label>
    </div>

    <div class="stack">
        <div class="form-row">
            <div>
                <label for="supplier">Lieferant</label>
                <input id="supplier" name="supplier" value="{{ old('supplier', $sparePart->supplier) }}">
            </div>
            <div>
                <label for="supplier_ref">Lieferantenartikelnummer</label>
                <input id="supplier_ref" name="supplier_ref" value="{{ old('supplier_ref', $sparePart->supplier_ref) }}">
            </div>
        </div>

        <div>
            <label for="manufacturer_part_number">Hersteller-Artikelnummer</label>
            <input id="manufacturer_part_number" name="manufacturer_part_number" value="{{ old('manufacturer_part_number', $sparePart->manufacturer_part_number) }}">
        </div>

        <div>
            <label for="eans">EANs</label>
            <textarea id="eans" name="eans" placeholder="Eine EAN pro Zeile oder kommagetrennt">{{ old('eans', $eanInput) }}</textarea>
            <p class="muted">Mehrere EANs sind moeglich. Die Scan-Suche findet alle hinterlegten EANs.</p>
        </div>

        <div class="form-row">
            <div>
                <label for="purchase_price">EK netto</label>
                <input id="purchase_price" type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $sparePart->purchase_price) }}">
            </div>
            <div>
                <label for="sales_price">VK netto</label>
                <input id="sales_price" type="number" step="0.01" min="0" name="sales_price" value="{{ old('sales_price', $sparePart->sales_price ?? 0) }}" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="vat_rate">MwSt. %</label>
                <input id="vat_rate" type="number" step="0.01" min="0" max="100" name="vat_rate" value="{{ old('vat_rate', $sparePart->vat_rate ?? 19) }}" required>
            </div>
            <div>
                <label for="stock_quantity">Bestand</label>
                <input id="stock_quantity" type="number" step="0.01" min="-999999" name="stock_quantity" value="{{ old('stock_quantity', number_format((float) ($sparePart->stock_quantity ?? 0), 2, '.', '')) }}">
            </div>
        </div>

        <div>
            <label for="minimum_stock">Mindestbestand</label>
            <input id="minimum_stock" type="number" step="0.01" min="0" name="minimum_stock" value="{{ old('minimum_stock', $sparePart->minimum_stock !== null ? number_format((float) $sparePart->minimum_stock, 2, '.', '') : null) }}">
        </div>

        <div>
            <label for="compatible_machine_ids">Kompatible Dolibarr-Maschinen-Produkt-IDs oder Refs</label>
            <textarea id="compatible_machine_ids" name="compatible_machine_ids" placeholder="Eine Maschinenreferenz (z.B. MACH-001) oder ID pro Zeile, kommagetrennt">{{ old('compatible_machine_ids', $compatibilityInput) }}</textarea>
            <p class="muted">
                Maschinenreferenzen bevorzugt (stabiler), aber auch IDs mÃ¶glich.
                Beispiele: MACH-001, 12345, oder Gemisch "MACH-001, 12345"
            </p>
        </div>
    </div>
</div>

```
