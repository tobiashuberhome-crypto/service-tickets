# Datei: resources\views\customer-portal-requests\show.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\customer-portal-requests\show.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Kundenanfrage pruefen</h1>
            <p class="muted">{{ $portalRequest->company_name }} Â· Status: {{ $portalRequest->statusLabel() }}</p>
        </div>
        <a class="btn secondary" href="{{ route('customer-portal-requests.index') }}">Zurueck</a>
    </div>

    <div class="grid grid-2">
        <section class="panel panel-body stack">
            <div class="section-title"><h2>Anfragedaten</h2></div>
            <dl class="definition-list">
                <dt>Firma / Name</dt><dd>{{ $portalRequest->company_name }}</dd>
                <dt>Ansprechpartner</dt><dd>{{ $portalRequest->contact_name ?: '-' }}</dd>
                <dt>E-Mail</dt><dd>{{ $portalRequest->email }}</dd>
                <dt>Telefon</dt><dd>{{ $portalRequest->phone ?: '-' }}</dd>
                <dt>Adresse</dt><dd>{{ $portalRequest->street ?: '-' }}<br>{{ trim(($portalRequest->zip ?? '').' '.($portalRequest->city ?? '')) ?: '-' }}</dd>
                <dt>Kundennummer</dt><dd>{{ $portalRequest->customer_number_input ?: '-' }}</dd>
                <dt>Maschinen-Seriennummer</dt><dd>{{ $portalRequest->machine_serial ?: '-' }}</dd>
                <dt>Rechnungs-/Auftragsnummer</dt><dd>{{ $portalRequest->invoice_or_order_number ?: '-' }}</dd>
                <dt>Nachricht</dt><dd>{{ $portalRequest->message ?: '-' }}</dd>
                <dt>Verknuepfter Kunde</dt><dd>{{ $portalRequest->matched_customer_name ?: '-' }} @if ($portalRequest->matched_dolibarr_thirdparty_id) (ID {{ $portalRequest->matched_dolibarr_thirdparty_id }}) @endif</dd>
                <dt>Notiz</dt><dd>{{ $portalRequest->review_note ?: '-' }}</dd>
            </dl>
        </section>

        <section class="panel panel-body stack">
            <div>
                <h2>Dolibarr-Kunde suchen</h2>
                <p class="muted">Suche nach Firma, Name oder Kundennummer. Danach passenden Treffer verknuepfen.</p>
            </div>
            <form method="get" class="button-row">
                <input name="lookup" value="{{ $lookup }}" placeholder="Suchbegriff">
                <button class="btn secondary" type="submit">Suchen</button>
            </form>

            @if ($lookupWarning)
                <div class="alert warning">Dolibarr-Suche fehlgeschlagen: {{ $lookupWarning }}</div>
            @endif

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Kundennummer</th>
                        <th>PLZ / Ort</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($matches as $customer)
                        <tr>
                            <td>{{ $customer['id'] }}</td>
                            <td>{{ $customer['name'] }}</td>
                            <td>{{ $customer['code_client'] ?? '-' }}</td>
                            <td>{{ trim(($customer['zip'] ?? '').' '.($customer['town'] ?? '')) ?: '-' }}</td>
                            <td>
                                <form method="post" action="{{ route('customer-portal-requests.link', $portalRequest) }}">
                                    @csrf
                                    <input type="hidden" name="dolibarr_thirdparty_id" value="{{ $customer['id'] }}">
                                    <button class="btn secondary" type="submit">Verknuepfen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Keine Treffer.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <details>
                <summary>Neuen Dolibarr-Kunden aus Anfrage anlegen</summary>
                <div class="details-body stack">
                    <p class="muted">Legt einen neuen Kunden in Dolibarr an und erstellt direkt den Portalzugang fuer diese E-Mail.</p>
                    <form method="post" action="{{ route('customer-portal-requests.create-customer', $portalRequest) }}" class="stack">
                        @csrf
                        <div>
                            <label for="review_note_new">Interne Notiz</label>
                            <textarea id="review_note_new" name="review_note"></textarea>
                        </div>
                        <button class="btn" type="submit">Neuen Dolibarr-Kunden anlegen</button>
                    </form>
                </div>
            </details>
        </section>
    </div>

    <section class="panel panel-body stack" style="margin-top: 18px">
        <h2>Status / Notiz</h2>
        <form method="post" action="{{ route('customer-portal-requests.status', $portalRequest) }}" class="stack">
            @csrf
            @method('patch')
            <div class="form-row">
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($portalRequest->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="review_note">Interne Notiz</label>
                    <input id="review_note" name="review_note" value="{{ old('review_note', $portalRequest->review_note) }}">
                </div>
            </div>
            <button class="btn secondary" type="submit">Status speichern</button>
        </form>
    </section>
@endsection

```
