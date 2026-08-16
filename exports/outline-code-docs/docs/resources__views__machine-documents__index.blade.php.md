# Datei: resources\views\machine-documents\index.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\machine-documents\index.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>PDF-Verknuepfungen</h1>
            <p class="muted">NextCloud- oder Freigabelinks je Dolibarr-Maschinentyp.</p>
        </div>
    </div>

    <form class="panel panel-body stack" method="post" action="{{ route('machine-documents.store') }}">
        @csrf
        <div class="grid grid-3">
            <div>
                <label for="machine_ref">Maschinen-Ref</label>
                <input id="machine_ref" name="machine_ref" required>
            </div>
            <div>
                <label for="machine_product_id">Dolibarr Produkt-ID Maschine (optional)</label>
                <input id="machine_product_id" type="number" min="1" name="machine_product_id">
            </div>
            <div>
                <label for="title">Titel</label>
                <input id="title" name="title" required>
            </div>
            <div>
                <label for="url">PDF-Link</label>
                <input id="url" type="url" name="url" required>
            </div>
        </div>
        <label class="check-row">
            <input type="checkbox" name="active" value="1" checked>
            Aktiv
        </label>
        <button class="btn" type="submit">Speichern</button>
    </form>

    <div style="height: 18px;"></div>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Maschinen-Ref</th>
                    <th>Maschinen-ID</th>
                    <th>Titel</th>
                    <th>Link</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td>
                            <input name="machine_ref" form="update-machine-document-{{ $document->id }}" value="{{ $document->machine_ref }}" required>
                        </td>
                        <td>
                            <input type="number" min="1" name="machine_product_id" form="update-machine-document-{{ $document->id }}" value="{{ $document->machine_product_id }}">
                        </td>
                        <td>
                            <input name="title" form="update-machine-document-{{ $document->id }}" value="{{ $document->title }}" required>
                        </td>
                        <td>
                            <input type="url" name="url" form="update-machine-document-{{ $document->id }}" value="{{ $document->url }}" required>
                            <div style="margin-top: 6px;">
                                <a href="{{ $document->url }}" target="_blank" rel="noopener">oeffnen</a>
                            </div>
                        </td>
                        <td>
                            <label class="check-row" style="justify-content:flex-start;">
                                <input type="checkbox" name="active" value="1" form="update-machine-document-{{ $document->id }}" @checked($document->active)>
                                {{ $document->active ? 'aktiv' : 'inaktiv' }}
                            </label>
                        </td>
                        <td>
                            <form id="update-machine-document-{{ $document->id }}" method="post" action="{{ route('machine-documents.update', $document) }}" style="margin-bottom: 8px;">
                                @csrf
                                @method('PUT')
                                <button class="btn" type="submit">Speichern</button>
                            </form>
                            <form method="post" action="{{ route('machine-documents.destroy', $document) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Loeschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">Noch keine PDF-Verknuepfungen.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

```
