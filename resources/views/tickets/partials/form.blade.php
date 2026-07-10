@php
    $machine = $ticket->customerMachine;
    $readonly = $ticket->exists && $ticket->isDone();
    $customerId = old('dolibarr_customer_id', $ticket->dolibarr_customer_id);
    $customerName = old('customer_name_snapshot', $ticket->customer_name_snapshot);
    $machineProductId = old('dolibarr_machine_product_id', $machine?->dolibarr_machine_product_id);
    $manufacturer = old('manufacturer_snapshot', $machine?->manufacturer_snapshot);
    $machineRef = old('machine_ref_snapshot', $machine?->machine_ref_snapshot);
    $serialNumber = old('serial_number', $machine?->serial_number);
@endphp

<input type="hidden" id="dolibarr_customer_id" name="dolibarr_customer_id" value="{{ $customerId }}">
<input type="hidden" id="customer_name_snapshot" name="customer_name_snapshot" value="{{ $customerName }}">
<input type="hidden" id="dolibarr_machine_product_id" name="dolibarr_machine_product_id" value="{{ $machineProductId }}">
<input type="hidden" id="manufacturer_snapshot" name="manufacturer_snapshot" value="{{ $manufacturer }}">
<input type="hidden" id="machine_ref_snapshot" name="machine_ref_snapshot" value="{{ $machineRef }}">

<div class="section">
    <div class="section-title">
        <h2>Kunde</h2>
    </div>
    <div class="stack">
        <div>
            <label for="customer_query">Name suchen</label>
            <div class="button-row">
                <input id="customer_query" value="{{ $customerName }}" placeholder="Kundenname" @disabled($readonly)>
                <button class="btn secondary" type="button" id="customer_search_btn" @disabled($readonly)>Suchen</button>
            </div>
            <div id="customer_selected" class="selected-value" @if (! $customerId) style="display:none" @endif>
                {{ $customerName }} @if ($customerId) (ID {{ $customerId }}) @endif
            </div>
            <div id="customer_results" class="lookup-results"></div>
        </div>

        @if (! $readonly)
            <details>
                <summary>Neuen Kunden anlegen</summary>
                <div class="details-body stack">
                    <div class="form-row">
                        <div>
                            <label for="new_customer_name">Name</label>
                            <input id="new_customer_name">
                        </div>
                        <div>
                            <label for="new_customer_email">E-Mail</label>
                            <input id="new_customer_email" type="email">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="new_customer_zip">PLZ</label>
                            <input id="new_customer_zip">
                        </div>
                        <div>
                            <label for="new_customer_town">Ort</label>
                            <input id="new_customer_town">
                        </div>
                    </div>
                    <div>
                        <label for="new_customer_address">Adresse</label>
                        <input id="new_customer_address">
                    </div>
                    <button class="btn secondary" type="button" id="customer_create_btn">Kunden in Dolibarr anlegen</button>
                </div>
            </details>
        @endif
    </div>
</div>

<div class="section">
    <div class="section-title">
        <h2>Maschine</h2>
    </div>
    <div class="stack">
        <div class="form-row">
            <div>
                <label for="machine_manufacturer_query">Hersteller</label>
                <select id="machine_manufacturer_query" @disabled($readonly)>
                    <option value="">Alle Hersteller</option>
                    @if ($manufacturer)
                        <option value="{{ $manufacturer }}" selected>{{ $manufacturer }}</option>
                    @endif
                </select>
            </div>
            <div>
                <label for="machine_ref_query">Typ / ref</label>
                <div class="button-row">
                    <input id="machine_ref_query" value="{{ $machineRef }}" @disabled($readonly)>
                    <button class="btn secondary" type="button" id="machine_search_btn" @disabled($readonly)>Suchen</button>
                </div>
            </div>
        </div>
        <div id="machine_selected" class="selected-value" @if (! $machineProductId) style="display:none" @endif>
            {{ $manufacturer }} / {{ $machineRef }} @if ($machineProductId) (Produkt-ID {{ $machineProductId }}) @endif
        </div>
        <div id="machine_results" class="lookup-results"></div>

        <div>
            <label for="serial_number">Seriennummer</label>
            <input id="serial_number" name="serial_number" value="{{ $serialNumber }}" @readonly($readonly)>
        </div>
        <div id="serial_history_hint" class="alert alert-info" style="display:none;"></div>

        @if (! $readonly)
            <details>
                <summary>Neue Maschine anlegen</summary>
                <div class="details-body stack">
                    <div class="form-row">
                        <div>
                            <label for="new_machine_manufacturer">Hersteller</label>
                            <input id="new_machine_manufacturer" value="{{ $manufacturer }}">
                        </div>
                        <div>
                            <label for="new_machine_ref">Typ / ref</label>
                            <input id="new_machine_ref">
                        </div>
                    </div>
                    <div>
                        <label for="new_machine_label">Bezeichnung</label>
                        <input id="new_machine_label">
                    </div>
                    <button class="btn secondary" type="button" id="machine_create_btn">Maschine in Dolibarr anlegen</button>
                </div>
            </details>
        @endif
    </div>
</div>

<dialog id="serial-history-dialog" style="width:min(960px, 96vw); border:none; border-radius:12px; padding:0;">
    <div class="panel panel-body stack" style="margin:0;">
        <div class="page-header" style="margin-bottom:0;">
            <div>
                <h2 style="margin:0;">Ticket-Historie zur Seriennummer</h2>
                <p class="muted" id="serial-history-dialog-subtitle" style="margin:6px 0 0;">-</p>
            </div>
            <button class="btn secondary" type="button" id="serial-history-dialog-close">Schliessen</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Status</th>
                        <th>Annahmedatum</th>
                        <th>Maschine</th>
                        <th>Kunde</th>
                        <th>Ansprechpartner</th>
                        <th>Erstellt</th>
                    </tr>
                </thead>
                <tbody id="serial-history-dialog-body">
                    <tr>
                        <td colspan="7" class="muted">Noch keine Historie geladen.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</dialog>

<div class="section">
    <div class="section-title">
        <h2>Leistungen</h2>
    </div>
    <div class="stack">
        <label class="check-row">
            <input type="checkbox" name="service_enabled" value="1" @checked(old('service_enabled', $ticket->service_enabled)) @disabled($readonly)>
            Service
        </label>
        <label class="check-row">
            <input type="checkbox" name="cleaning" value="1" @checked(old('cleaning', $ticket->cleaning)) @disabled($readonly)>
            Reinigung
        </label>
        <label class="check-row">
            <input type="checkbox" id="repair_enabled" name="repair_enabled" value="1" @checked(old('repair_enabled', $ticket->repair_enabled)) @disabled($readonly)>
            Reparatur
        </label>
        <label class="check-row">
            <input type="checkbox" name="spare_part_order_required" value="1" @checked(old('spare_part_order_required', $ticket->spare_part_order_required)) @disabled($readonly)>
            Ersatzteilbestellung aufnehmen
        </label>
        <div id="error_description_wrap">
            <label for="error_description">Fehlerbeschreibung</label>
            <textarea id="error_description" name="error_description" @readonly($readonly)>{{ old('error_description', $ticket->error_description) }}</textarea>
        </div>
        <div>
            <label for="technician_note">Techniker-Info (intern / fuer Kunde sichtbar)</label>
            <textarea id="technician_note" name="technician_note" @readonly($readonly)>{{ old('technician_note', $ticket->technician_note) }}</textarea>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">
        <h2>Termine</h2>
    </div>
    <div class="form-row">
        <div>
            <label for="acceptance_date">Annahme-Datum</label>
            <input id="acceptance_date" type="date" name="acceptance_date" value="{{ old('acceptance_date', optional($ticket->acceptance_date)->format('Y-m-d') ?: now()->toDateString()) }}" @readonly($readonly)>
        </div>
        <div>
            <label for="target_date">Zieldatum</label>
            <input id="target_date" type="date" name="target_date" value="{{ old('target_date', optional($ticket->target_date)->format('Y-m-d')) }}" @readonly($readonly)>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">
        <h2>Status</h2>
    </div>
    <select name="status" @disabled($readonly)>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $ticket->status ?: \App\Models\Ticket::STATUS_OPEN) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
