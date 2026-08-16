# Datei: resources\views\spare-parts\index.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\spare-parts\index.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Ersatzteile</h1>
            <p class="muted">Lokaler Ersatzteilkatalog mit Preisen, Bestand und Maschinen-Kompatibilitaeten.</p>
        </div>
        <a class="btn" href="{{ route('spare-parts.create') }}">Neues Ersatzteil</a>
    </div>

    <form class="panel panel-body" method="get" action="{{ route('spare-parts.index') }}">
        <div class="form-row">
            <div>
                <label for="q">Suche</label>
                <input id="q" name="q" value="{{ $search }}" placeholder="Ref, Bezeichnung, Hersteller, Hersteller-Artikelnr., EAN, Kategorie, Lagerplatz">
            </div>
            <div class="button-row" style="align-items: end;">
                <button class="btn" type="submit">Suchen</button>
                <a class="btn secondary" href="{{ route('spare-parts.index') }}">Zuruecksetzen</a>
            </div>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <form id="spare-part-scan-stock-form" class="panel panel-body" method="post" action="{{ route('spare-parts.scan-stock') }}">
        @csrf
        <div class="grid grid-3">
            <div>
                <label for="spare_part_scan_code">Code</label>
                <div class="button-row">
                    <input id="spare_part_scan_code" name="code" placeholder="Barcode / QR-Code">
                    <button class="btn secondary" type="button" id="spare_part_scan_btn">Code scannen</button>
                </div>
            </div>
            <div>
                <label for="spare_part_scan_direction">Buchung</label>
                <select id="spare_part_scan_direction" name="direction">
                    <option value="decrease">Bestand verringern</option>
                    <option value="increase">Bestand erhoehen</option>
                </select>
            </div>
            <div>
                <label for="spare_part_scan_quantity">Stueck</label>
                <input id="spare_part_scan_quantity" name="quantity" type="number" min="0.01" max="100" step="0.01" value="1.00">
            </div>
        </div>
        <div class="button-row" style="margin-top: 12px;">
            <button class="btn" type="submit">Bestand buchen</button>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Ref</th>
                    <th>Bezeichnung</th>
                    <th>Hersteller</th>
                    <th>Kategorie / Typ</th>
                    <th>EANs</th>
                    <th>Lagerplatz</th>
                    <th>VK</th>
                    <th>Bestand</th>
                    <th>Kompatibilitaeten</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($spareParts as $part)
                    <tr>
                        <td>{{ $part->part_ref }}</td>
                        <td>
                            <strong>{{ $part->label }}</strong>
                            @unless ($part->active)
                                <span class="badge error">inaktiv</span>
                            @endunless
                        </td>
                        <td>{{ $part->manufacturer ?: '-' }}
                            @if ($part->manufacturer_part_number)
                                <div class="muted">{{ $part->manufacturer_part_number }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $part->category?->name ?: '-' }}
                            @if ($part->spare_part_type)
                                <div class="muted">{{ $part->spare_part_type }}</div>
                            @endif
                        </td>
                        <td>{{ $part->eans->pluck('ean')->implode(', ') ?: '-' }}</td>
                        <td>
                            {{ $part->storage_location_1 ?: '-' }}
                            @if ($part->storage_location_2)
                                <div class="muted">{{ $part->storage_location_2 }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) $part->sales_price, 2, ',', '.') }} EUR</td>
                        <td>{{ $part->stockLabel() }}</td>
                        <td>{{ $part->compatibilities->count() }}</td>
                        <td><a class="btn secondary" href="{{ route('spare-parts.edit', $part) }}">Bearbeiten</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="muted">Keine Ersatzteile gefunden.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($spareParts->hasPages())
        <div class="button-row pagination">
            @if ($spareParts->onFirstPage())
                <span class="btn secondary" aria-disabled="true">Zurueck</span>
            @else
                <a class="btn secondary" href="{{ $spareParts->previousPageUrl() }}">Zurueck</a>
            @endif
            <span class="muted">Seite {{ $spareParts->currentPage() }} von {{ $spareParts->lastPage() }}</span>
            @if ($spareParts->hasMorePages())
                <a class="btn secondary" href="{{ $spareParts->nextPageUrl() }}">Weiter</a>
            @else
                <span class="btn secondary" aria-disabled="true">Weiter</span>
            @endif
        </div>
    @endif
@endsection

@push('scripts')
<script>
(() => {
    const button = document.getElementById('spare_part_scan_btn');
    const code = document.getElementById('spare_part_scan_code');
    const form = document.getElementById('spare-part-scan-stock-form');

    if (!button || !code || !form || !window.ServiceTicketScanner) {
        return;
    }

    button.addEventListener('click', () => {
        window.ServiceTicketScanner.open({
            onDetected: (value) => {
                code.value = value;
                form.submit();
            },
        });
    });
})();
</script>
@endpush

```
