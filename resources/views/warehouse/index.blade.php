@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Lagerverwaltung</h1>
            <p class="muted">Bestandsuebersicht mit Filtern nach Hersteller, Ersatzteil-Typ, Kategorie und Mindestbestand.</p>
        </div>
        <a class="btn" href="{{ route('spare-parts.create') }}">Neues Ersatzteil</a>
    </div>

    <form class="panel panel-body" method="get" action="{{ route('warehouse.index') }}">
        <div class="grid grid-3">
            <div>
                <label for="q">Suche</label>
                <input id="q" name="q" value="{{ $search }}" placeholder="Ref, Bezeichnung, EAN, Lagerplatz">
            </div>
            <div>
                <label for="manufacturer">Hersteller</label>
                <select id="manufacturer" name="manufacturer">
                    <option value="">Alle Hersteller</option>
                    @foreach ($manufacturers as $item)
                        <option value="{{ $item }}" @selected($manufacturer === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type">Ersatzteil-Typ</label>
                <select id="type" name="type">
                    <option value="">Alle Typen</option>
                    @foreach ($types as $item)
                        <option value="{{ $item }}" @selected($type === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category_id">Kategorie</label>
                <select id="category_id" name="category_id">
                    <option value="">Alle Kategorien</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="check-row" style="margin-top: 26px;">
                    <input type="checkbox" name="low_stock" value="1" @checked($lowStock)>
                    Bestand &lt;= Mindestbestand
                </label>
            </div>
            <div class="button-row" style="align-items: end;">
                <button class="btn" type="submit">Filtern</button>
                <a class="btn secondary" href="{{ route('warehouse.index') }}">Zuruecksetzen</a>
            </div>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Artikelnummer</th>
                    <th>Bezeichnung</th>
                    <th>Hersteller</th>
                    <th>Kategorie / Typ</th>
                    <th>EANs</th>
                    <th>Lagerplatz</th>
                    <th>Bestand</th>
                    <th>Mindestbestand</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($spareParts as $part)
                    @php
                        $isLow = $part->minimum_stock !== null && (float) $part->stock_quantity <= (float) $part->minimum_stock;
                    @endphp
                    <tr>
                        <td>{{ $part->part_ref }}</td>
                        <td><strong>{{ $part->label }}</strong></td>
                        <td>
                            {{ $part->manufacturer ?: '-' }}
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
                        <td>{{ $part->stockLabel() }}</td>
                        <td>{{ $part->minimum_stock !== null ? number_format((float) $part->minimum_stock, 2, ',', '.').' '.$part->unit : '-' }}</td>
                        <td>
                            @if ($isLow)
                                <span class="badge error">nachbestellen</span>
                            @else
                                <span class="badge synced">ok</span>
                            @endif
                        </td>
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
