@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</h1>
            <p class="muted">
                Ticket {{ $ticket->ticket_number }}
                @if ($ticket->dolibarr_order_ref)
                    · Dolibarr-Auftrag {{ $ticket->dolibarr_order_ref }}
                @endif
                @if ($ticket->dolibarr_invoice_ref)
                    · Rechnung {{ $ticket->dolibarr_invoice_ref }}
                @endif
            </p>
        </div>
        <div class="button-row">
            <span class="badge {{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
            <span class="badge {{ $ticket->sync_status }}">{{ $ticket->syncStatusLabel() }}</span>
            @if ($ticket->spare_part_order_required)
                <span class="badge order-required">Ersatzteilbestellung</span>
            @endif
            @if ($ticket->created_via_customer_portal)
                <span class="badge">Kundenportal</span>
            @endif
            <a class="btn secondary" id="invoice-open-btn" href="{{ route('tickets.geiser-invoice', $ticket) }}" data-mail-url="{{ route('tickets.geiser-invoice', ['ticket' => $ticket, 'send_mail' => 1]) }}" target="_blank" rel="noopener">Rechnung</a>
            <a class="btn secondary" href="{{ route('tickets.index') }}">Zurueck</a>
        </div>
    </div>

    @if ($ticket->sync_message)
        <div class="alert warning">{{ $ticket->sync_message }}</div>
    @endif

    @if ($ticket->created_via_customer_portal)
        <div class="alert success">Dieses Ticket wurde vom Kundenportal erstellt. Kontakt: {{ $ticket->customer_contact_name_snapshot ?: '-' }} / {{ $ticket->customer_email_snapshot ?: '-' }}</div>
    @endif

    <dialog id="invoice-mail-dialog" style="width:min(560px, 94vw); border:none; border-radius:12px; padding:0;">
        <div class="panel panel-body stack" style="margin:0;">
            <h3 style="margin:0;">Rechnung versenden?</h3>
            <p class="muted" style="margin:0;">Soll die Rechnung zusaetzlich direkt per E-Mail versendet werden?</p>
            <div class="button-row" style="justify-content:flex-end; margin-top:8px;">
                <button type="button" class="btn secondary" id="invoice-open-only-btn">Nein, nur oeffnen</button>
                <button type="button" class="btn" id="invoice-send-mail-btn">Ja, senden</button>
            </div>
        </div>
    </dialog>

    @if ($ticket->customer_photo_path)
    <div class="panel panel-body" style="margin-bottom: 16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <h3 style="margin:0;">📷 Foto des Reparaturauftrags (vom Kunden)</h3>
            <a href="{{ Storage::disk('public')->url($ticket->customer_photo_path) }}"
               target="_blank" class="btn secondary" style="font-size:13px;">
                Vollbild öffnen
            </a>
        </div>
        <img src="{{ Storage::disk('public')->url($ticket->customer_photo_path) }}"
             alt="Foto Reparaturauftrag"
             style="max-width:100%; max-height:500px; border-radius:6px; border:1px solid #ddd; cursor:pointer;"
             onclick="window.open(this.src,'_blank')">
    </div>
    @endif

    <div class="ticket-layout">
        <form method="post" action="{{ route('tickets.update', $ticket) }}" class="panel panel-body">
            @csrf
            @method('PUT')
            @include('tickets.partials.form', ['ticket' => $ticket])

            <div class="button-row">
                @if (! $ticket->isDone())
                    <button class="btn" type="submit">Speichern</button>
                @endif
                @if ($ticket->status === \App\Models\Ticket::STATUS_OPEN)
                    <form method="post" action="{{ route('tickets.activate-order', $ticket) }}" style="display:inline;">
                        @csrf
                        <button class="btn secondary" type="submit">Auftrag aktivieren</button>
                    </form>
                @elseif ($ticket->status === \App\Models\Ticket::STATUS_IN_PROGRESS)
                    <form method="post" action="{{ route('tickets.close-order-invoice', $ticket) }}" style="display:inline;">
                        @csrf
                        <button class="btn secondary" type="submit">Intern erledigt &amp; Rechnung anlegen</button>
                    </form>
                @elseif ($ticket->status === \App\Models\Ticket::STATUS_INTERNALLY_DONE)
                    <form method="post" action="{{ route('tickets.activate-invoice', $ticket) }}" style="display:inline;">
                        @csrf
                        <button class="btn secondary" type="submit">Rechnung aktivieren</button>
                    </form>
                @endif
                @if ($ticket->sync_status === \App\Models\Ticket::SYNC_ERROR)
                    <button class="btn secondary" type="submit" form="retry-sync-form">Sync erneut versuchen</button>
                @endif
            </div>
        </form>

        <aside class="stack">
            <div class="panel panel-body">
                <div class="section-title">
                    <h2>Ersatzteile</h2>
                </div>

                @if ($documents->isNotEmpty())
                    <div class="stack" style="margin-bottom: 12px;">
                        @foreach ($documents as $document)
                            <a class="btn secondary" href="{{ $document->url }}" target="_blank" rel="noopener">
                                PDF: {{ $document->title }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($documents->isEmpty())
                    <p class="muted">Fuer diese Maschine ist noch kein PDF-Link hinterlegt.</p>
                @endif

                @if (! $ticket->isDone())
                    <form id="ticket-scan-part-form" method="post" action="{{ route('tickets.parts.scan', $ticket) }}" class="stack" style="margin-bottom: 14px;">
                        @csrf
                        <div class="grid grid-3">
                            <div>
                                <label for="ticket_scan_code">Code</label>
                                <input id="ticket_scan_code" name="code" placeholder="Barcode / QR-Code">
                            </div>
                            <div>
                                <label for="ticket_scan_quantity">Stueck</label>
                                <input id="ticket_scan_quantity" name="quantity" type="number" min="0.01" max="100" step="0.01" value="1.00">
                            </div>
                            <div class="button-row" style="align-items: end;">
                                <button class="btn secondary" type="button" id="ticket_scan_btn">Code scannen</button>
                                <button class="btn" type="submit">Hinzufuegen</button>
                            </div>
                        </div>
                    </form>
                @endif

                <details @if ($partsMode || $partSearch) open @endif>
                    <summary>Ersatzteile suchen</summary>
                    <div class="details-body stack">
                        <form method="get" action="{{ route('tickets.show', $ticket) }}">
                            <div class="grid grid-3">
                                <div>
                                    <label for="part_manufacturer">Hersteller</label>
                                    <select id="part_manufacturer" name="part_manufacturer" data-selected="{{ $partManufacturer }}">
                                        <option value="">Alle Hersteller</option>
                                        @if ($partManufacturer)
                                            <option value="{{ $partManufacturer }}" selected>{{ $partManufacturer }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label for="part_category">Kategorie</label>
                                    <select id="part_category" name="part_category" data-selected="{{ request()->get('part_category') }}">
                                        <option value="">Alle Kategorien</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="part_machine_ref">Typ</label>
                                    <input id="part_machine_ref" name="part_machine_ref" value="{{ $partMachineRef }}" placeholder="leer = alle Typen des Herstellers">
                                </div>
                                <div>
                                    <label for="part_search">Ersatzteil-Ref / Bezeichnung</label>
                                    <input id="part_search" name="part_search" value="{{ $partSearch }}" placeholder="Suche in Ref & Bezeichnung">
                                </div>
                            </div>
                            <div class="button-row" style="margin-top: 10px;">
                                <button class="btn" type="submit">Suchen</button>
                                <button class="btn secondary" type="submit" name="parts" value="all">Alles anzeigen</button>
                            </div>
                        </form>

                        @if ($partsWarning)
                            <div class="alert warning">{{ $partsWarning }}</div>
                        @endif

                            @if ($availableParts->isNotEmpty())
                            <div class="table-wrap" style="max-height:360px; overflow:auto;">
                                <table>
                                    <thead>
                                    <tr>
                                        <th>Ref</th>
                                        <th>Bezeichnung</th>
                                        <th>Preis</th>
                                        <th>Bestand</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($availableParts as $part)
                                        <tr>
                                            <td>{{ $part->part_ref }}</td>
                                            <td>
                                                <strong>{{ $part->label }}</strong>
                                                @if ($part->description)
                                                    <div class="muted">{{ \Illuminate\Support\Str::limit($part->description, 90) }}</div>
                                                @endif
                                            </td>
                                            <td>{{ number_format((float) $part->sales_price, 2, ',', '.') }} EUR</td>
                                            <td>{{ $part->stockLabel() }}</td>
                                            <td>
                                                @if (! $ticket->isDone())
                                                    <form method="post" action="{{ route('tickets.parts.store', $ticket) }}" class="button-row">
                                                        @csrf
                                                        <input type="hidden" name="spare_part_id" value="{{ $part->id }}">
                                                        <input style="width: 90px;" type="number" step="0.01" min="0.01" max="100" name="quantity" value="1.00">
                                                        <button class="btn secondary" type="submit">Hinzufuegen</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($partsMode || $partSearch)
                            <p class="muted">Keine passenden Ersatzteile gefunden.</p>
                        @endif
                    </div>
                </details>
            </div>

            <div class="panel panel-body">
                @if ($ticket->customerMachineProfile)
                    <div class="section-title">
                        <h2>Kundendaten aus Portal</h2>
                    </div>
                    <div class="stack" style="margin-bottom: 16px;">
                        <div><strong>Ansprechpartner:</strong> {{ $ticket->customerMachineProfile->contact_name ?: '-' }}</div>
                        <div><strong>E-Mail:</strong> {{ $ticket->customerMachineProfile->email ?: '-' }}</div>
                        <div><strong>Telefon:</strong> {{ $ticket->customerMachineProfile->phone ?: '-' }}</div>
                        <div><strong>Adresse:</strong>
                            {{ collect([$ticket->customerMachineProfile->street, trim(implode(' ', array_filter([$ticket->customerMachineProfile->zip, $ticket->customerMachineProfile->city])))])->filter()->implode(', ') ?: '-' }}
                        </div>
                        <div><strong>Garantie:</strong> {{ $ticket->customerMachineProfile->warranty_claimed ? 'ja' : 'nein' }}</div>
                        <div><strong>Zubehoer:</strong> {{ $ticket->customerMachineProfile->accessoriesSummary() ?: '-' }}</div>
                        <div><strong>Freigabe ohne Ruecksprache bis:</strong>
                            @if ($ticket->customerMachineProfile->repair_approval_limit !== null)
                                {{ number_format((float) $ticket->customerMachineProfile->repair_approval_limit, 2, ',', '.') }} EUR
                            @else
                                -
                            @endif
                        </div>
                        @if ($ticket->customerMachineProfile->intake_note)
                            <div><strong>Zusatzinfo:</strong> {{ $ticket->customerMachineProfile->intake_note }}</div>
                        @endif
                    </div>
                @endif

                @if (! empty($ticket->customer_portal_estimate_lines))
                    <h2>Geiser: Voraussichtliche interne Positionen</h2>
                    <div class="table-wrap" style="margin-bottom: 16px;">
                        <table>
                            <thead>
                            <tr>
                                <th>Artikel</th>
                                <th>Hinweis</th>
                                <th>Menge</th>
                                <th>Einzelpreis</th>
                                <th>Gesamt</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($ticket->customer_portal_estimate_lines as $line)
                                <tr>
                                    <td>{{ $line['label'] ?? '-' }}</td>
                                    <td>{{ $line['hint'] ?? '-' }}</td>
                                    <td>{{ number_format((float) ($line['quantity'] ?? 0), 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($line['unit_price'] ?? 0), 2, ',', '.') }} EUR</td>
                                    <td>{{ number_format((float) ($line['line_total'] ?? 0), 2, ',', '.') }} EUR</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="4" style="text-align: right;">Geschaetzte Gesamtkosten</th>
                                <th>{{ number_format((float) ($ticket->customer_portal_estimate_total ?? 0), 2, ',', '.') }} EUR</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                @if (! $ticket->isDone())
                    <h2>Manuelle Rechnungspositionen</h2>
                    <form method="post" action="{{ route('tickets.manual-lines.store', $ticket) }}" id="manual-lines-form" class="stack" style="margin-bottom: 14px;">
                        @csrf
                        <div id="manual-lines-container" class="stack">
                            <div class="grid grid-4 manual-line-row" data-manual-line-row>
                                <div>
                                    <label>Ref</label>
                                    <input name="manual_lines[0][part_ref]" required>
                                </div>
                                <div>
                                    <label>Bezeichnung</label>
                                    <input name="manual_lines[0][label]" required>
                                </div>
                                <div>
                                    <label>Menge</label>
                                    <input type="number" name="manual_lines[0][quantity]" value="1.00" min="0.01" max="100" step="0.01" required>
                                </div>
                                <div>
                                    <label>Preis (netto)</label>
                                    <input type="number" name="manual_lines[0][sales_price]" value="0.00" min="0" max="999999.99" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="button-row">
                            <button class="btn secondary" type="button" id="add-manual-line-btn" title="Weitere Zeile">+</button>
                            <button class="btn" type="submit">Positionen hinzufuegen</button>
                        </div>
                    </form>
                @endif

                <h2>Ausgewaehlte Ersatzteile / Rechnungspositionen</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Bezeichnung</th>
                            <th>Menge</th>
                            <th>Preis</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($ticket->parts as $part)
                            <tr>
                                <td>{{ $part->part_ref_snapshot }}</td>
                                <td>
    {{ $part->label_snapshot }}
    @if (! $part->spare_part_id)
        <div class="muted">manuelle Position</div>
    @endif
</td>
                                <td>{{ number_format((float) $part->quantity, 2, ',', '.') }} {{ $part->unit_snapshot }}</td>
                                <td>{{ number_format($part->totalNet(), 2, ',', '.') }} EUR</td>
                                <td>
                                    @if (! $ticket->isDone() && ! $part->dolibarr_order_line_id)
                                        <form method="post" action="{{ route('tickets.parts.destroy', [$ticket, $part]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Entfernen</button>
                                        </form>
                                    @elseif ($part->dolibarr_order_line_id)
                                        <span class="badge synced">uebertragen</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">Noch keine Ersatzteile zugeordnet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($ticket->serviceLines->isNotEmpty())
                <div class="panel panel-body">
                    <h2>Vorbereitete Serviceleistungen</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Ref</th>
                                <th>Bezeichnung</th>
                                <th>Menge</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($ticket->serviceLines as $line)
                                <tr>
                                    <td>{{ $line->product_ref }}</td>
                                    <td>{{ $line->label_snapshot }}</td>
                                    <td>
                                        @if (! $ticket->isDone() && ! $line->dolibarr_order_line_id)
                                            <form method="post" action="{{ route('tickets.service-lines.update', [$ticket, $line]) }}" class="button-row">
                                                @csrf
                                                @method('PUT')
                                                <input style="width: 90px;" type="number" name="quantity" value="{{ number_format((float) $line->quantity, 2, '.', '') }}" min="0.01" max="100" step="0.01">
                                                <button class="btn secondary" type="submit">Speichern</button>
                                            </form>
                                        @else
                                            {{ number_format((float) $line->quantity, 2, ',', '.') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($line->dolibarr_order_line_id)
                                            <span class="badge synced">uebertragen</span>
                                        @else
                                            <span class="badge">vorbereitet</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </aside>
    </div>

    <form id="retry-sync-form" method="post" action="{{ route('tickets.retry-sync', $ticket) }}">
        @csrf
    </form>
@endsection

@include('tickets.partials.form-scripts')

@push('scripts')
<script>
(() => {
    const ticketScanButton = document.getElementById('ticket_scan_btn');
    const ticketScanCode = document.getElementById('ticket_scan_code');
    const ticketScanForm = document.getElementById('ticket-scan-part-form');

    if (ticketScanButton && ticketScanCode && ticketScanForm && window.ServiceTicketScanner) {
        ticketScanButton.addEventListener('click', () => {
            window.ServiceTicketScanner.open({
                onDetected: (value) => {
                    ticketScanCode.value = value;
                    ticketScanForm.submit();
                },
            });
        });
    }

    const manualLinesContainer = document.getElementById('manual-lines-container');
    const addManualLineBtn = document.getElementById('add-manual-line-btn');

    if (manualLinesContainer && addManualLineBtn) {
        addManualLineBtn.addEventListener('click', () => {
            const rows = manualLinesContainer.querySelectorAll('[data-manual-line-row]');
            const index = rows.length;
            const clone = rows[0].cloneNode(true);

            clone.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/manual_lines\[\d+\]/, `manual_lines[${index}]`);
                if (input.name.endsWith('[part_ref]') || input.name.endsWith('[label]')) {
                    input.value = '';
                } else if (input.name.endsWith('[quantity]')) {
                    input.value = '1.00';
                } else if (input.name.endsWith('[sales_price]')) {
                    input.value = '0.00';
                }
            });

            manualLinesContainer.appendChild(clone);
        });
    }

    const manufacturerSelect = document.getElementById('part_manufacturer');
    if (!manufacturerSelect) {
        return;
    }

    const selected = manufacturerSelect.dataset.selected || manufacturerSelect.value;

    async function requestJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            throw new Error('Hersteller konnten nicht geladen werden.');
        }
        return response.json();
    }

    requestJson('{{ route('lookup.manufacturers') }}')
        .then((manufacturers) => {
            manufacturerSelect.innerHTML = '<option value="">Alle Hersteller</option>';
            if (selected && !manufacturers.some((manufacturer) => manufacturer.toLowerCase() === selected.toLowerCase())) {
                const option = document.createElement('option');
                option.value = selected;
                option.textContent = selected;
                manufacturerSelect.appendChild(option);
            }
            manufacturers.forEach((manufacturer) => {
                const option = document.createElement('option');
                option.value = manufacturer;
                option.textContent = manufacturer;
                manufacturerSelect.appendChild(option);
            });
            manufacturerSelect.value = selected;
        })
        .catch(() => {
            if (selected) {
                manufacturerSelect.innerHTML = '';
                const option = document.createElement('option');
                option.value = selected;
                option.textContent = selected;
                manufacturerSelect.appendChild(option);
            }
        });

    const categorySelect = document.getElementById('part_category');
    if (categorySelect) {
        const selectedCat = categorySelect.dataset.selected || '';

        requestJson('{{ route('lookup.part-categories') }}')
            .then((categories) => {
                categorySelect.innerHTML = '<option value="">Alle Kategorien</option>';
                if (selectedCat && !categories.some((c) => String(c.id) === String(selectedCat))) {
                    const opt = document.createElement('option');
                    opt.value = selectedCat;
                    opt.textContent = selectedCat;
                    categorySelect.appendChild(opt);
                }

                categories.forEach((c) => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    categorySelect.appendChild(opt);
                });
                if (selectedCat) categorySelect.value = selectedCat;
            })
            .catch(() => {
                // leave default
            });
    }
})();
</script>
<script>
(() => {
    const invoiceButton = document.getElementById('invoice-open-btn');
    const invoiceDialog = document.getElementById('invoice-mail-dialog');
    const openOnlyButton = document.getElementById('invoice-open-only-btn');
    const sendMailButton = document.getElementById('invoice-send-mail-btn');

    if (!invoiceButton || !invoiceDialog || !openOnlyButton || !sendMailButton) {
        return;
    }

    function openInvoice(sendMail) {
        const targetUrl = sendMail ? invoiceButton.dataset.mailUrl : invoiceButton.href;
        window.open(targetUrl, '_blank', 'noopener');
    }

    invoiceButton.addEventListener('click', (event) => {
        event.preventDefault();
        invoiceDialog.showModal();
    });

    openOnlyButton.addEventListener('click', () => {
        invoiceDialog.close();
        openInvoice(false);
    });

    sendMailButton.addEventListener('click', () => {
        invoiceDialog.close();
        openInvoice(true);
    });

    invoiceDialog.addEventListener('click', (event) => {
        const rect = invoiceDialog.getBoundingClientRect();
        const clickedInDialog = rect.top <= event.clientY
            && event.clientY <= rect.top + rect.height
            && rect.left <= event.clientX
            && event.clientX <= rect.left + rect.width;

        if (!clickedInDialog) {
            invoiceDialog.close();
        }
    });
})();
</script>
@endpush
