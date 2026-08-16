@extends('layouts.customer-portal-geiser')

@section('content')
    <div class="page-header">
        <div>
            <h1>Ticket {{ $ticket->ticket_number }}</h1>
            <p class="muted">Status: <strong>{{ $customerStatusLabel }}</strong></p>
        </div>
        <div class="button-row">
            <a class="btn secondary" href="{{ route('geiser-portal.tickets.print', $ticket) }}" target="_blank" rel="noopener">Ticket drucken</a>
            <a class="btn secondary" href="{{ route('geiser-portal.tickets.work-report', $ticket) }}" target="_blank" rel="noopener">Arbeitsbericht PDF</a>
            @if (($customerEmail ?? '') !== '')
                <form method="post" action="{{ route('geiser-portal.tickets.work-report.mail', $ticket) }}" style="display:inline;">
                    @csrf
                    <button class="btn secondary" type="submit">Arbeitsbericht per E-Mail</button>
                </form>
            @endif
            <a class="btn secondary" href="{{ route('geiser-portal.dashboard') }}">Zurueck zur Übersicht</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('auto_open_print_url'))
        <div class="alert alert-info">
            Der Ausdruck wird in einem neuen Tab geoeffnet. Falls der Browser Popups blockiert:
            <a href="{{ session('auto_open_print_url') }}" target="_blank" rel="noopener">hier klicken</a>.
        </div>
        <script>
            window.addEventListener('load', function () {
                window.open(@json(session('auto_open_print_url')), '_blank', 'noopener');
            });
        </script>
    @endif

    @if (!$ticket->created_via_customer_portal)
        <div class="alert alert-info">
            Dieses Ticket wurde intern angelegt und kann hier im Il Coccolino-Portal weiter bearbeitet werden.
        </div>
    @endif

    <div class="grid grid-2">
        <!-- Maschine / Profil -->
        <div class="panel panel-body stack">
            <h3>Maschine</h3>
            @if ($isEditable)
                <div class="grid grid-3">
                    <div>
                        <label for="serial_number">Seriennummer *</label>
                        <input id="serial_number" name="serial_number" value="{{ old('serial_number', $ticket->customerMachineProfile?->serial_number ?: $ticket->customerMachine?->serial_number) }}" required form="ticket-edit-form">
                        @error('serial_number') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="manufacturer_snapshot">Hersteller</label>
                        <input id="manufacturer_snapshot" name="manufacturer_snapshot" value="{{ old('manufacturer_snapshot', $ticket->customerMachineProfile?->manufacturer_snapshot ?: $ticket->customerMachine?->manufacturer_snapshot) }}" form="ticket-edit-form">
                        @error('manufacturer_snapshot') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="machine_ref_snapshot">Maschinen-Typ / Modell *</label>
                        <input id="machine_ref_snapshot" name="machine_ref_snapshot" value="{{ old('machine_ref_snapshot', $ticket->customerMachineProfile?->machine_ref_snapshot ?: $ticket->customerMachine?->machine_ref_snapshot) }}" required form="ticket-edit-form">
                        @error('machine_ref_snapshot') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><h3>Foto des Reparaturauftrags</h3></div>
                    <p class="muted">Sie koennen ein bestehendes Foto durch eine neue Aufnahme oder Datei ersetzen.</p>
                    <input type="file" name="customer_photo" id="customer_photo_final" accept="image/*" style="display:none" form="ticket-edit-form">

                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                        <button type="button" id="btn-camera" class="btn secondary" style="font-size:15px;">📷 Kamera oeffnen</button>
                        <button type="button" id="btn-file" class="btn secondary" style="font-size:15px;">📁 Datei auswaehlen</button>
                        <span id="photo-filename" style="color:#555; font-size:13px;"></span>
                    </div>

                    <input type="file" id="input-camera" accept="image/*" capture="environment" style="display:none">
                    <input type="file" id="input-file" accept="image/*" style="display:none">

                    @if ($ticket->customer_photo_path)
                        <div style="margin-top:12px;">
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->customer_photo_path) }}" target="_blank" rel="noopener">Aktuelles Foto oeffnen</a>
                            <div style="margin-top:8px;">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->customer_photo_path) }}" alt="Aktuelles Ticketfoto" style="max-width:100%; max-height:300px; border-radius:6px; border:1px solid #ddd;">
                            </div>
                        </div>
                    @endif

                    <div id="photo-preview-wrap" style="display:none; margin-top:12px;">
                        <img id="photo-preview" src="" alt="Vorschau" style="max-width:100%; max-height:300px; border-radius:6px; border:1px solid #ddd;">
                        <br>
                        <button type="button" id="btn-remove-photo" class="btn secondary" style="margin-top:8px; font-size:12px;">✕ Neue Fotoauswahl entfernen</button>
                    </div>
                    @error('customer_photo') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="section">
                    <div class="section-title"><h3>Annahme / Zubehoer</h3></div>
                    <label class="check-row">
                        <input type="checkbox" name="warranty_claimed" value="1" @checked(old('warranty_claimed', $ticket->customerMachineProfile?->warranty_claimed)) form="ticket-edit-form">
                        Garantie
                    </label>
                    <div class="grid grid-3">
                        <label class="check-row"><input type="checkbox" name="accessory_presser_foot" value="1" @checked(old('accessory_presser_foot', $ticket->customerMachineProfile?->accessory_presser_foot)) form="ticket-edit-form"> Naehfuss</label>
                        <label class="check-row"><input type="checkbox" name="accessory_bobbin_case" value="1" @checked(old('accessory_bobbin_case', $ticket->customerMachineProfile?->accessory_bobbin_case)) form="ticket-edit-form"> Spulenkapsel</label>
                        <label class="check-row"><input type="checkbox" name="accessory_bobbin" value="1" @checked(old('accessory_bobbin', $ticket->customerMachineProfile?->accessory_bobbin)) form="ticket-edit-form"> Unterfadenspule</label>
                        <label class="check-row"><input type="checkbox" name="accessory_power_cable" value="1" @checked(old('accessory_power_cable', $ticket->customerMachineProfile?->accessory_power_cable)) form="ticket-edit-form"> Kabel</label>
                        <label class="check-row"><input type="checkbox" name="accessory_foot_pedal" value="1" @checked(old('accessory_foot_pedal', $ticket->customerMachineProfile?->accessory_foot_pedal)) form="ticket-edit-form"> Fussanlasser</label>
                        <label class="check-row"><input type="checkbox" name="accessory_case" value="1" @checked(old('accessory_case', $ticket->customerMachineProfile?->accessory_case)) form="ticket-edit-form"> Koffer</label>
                    </div>
                    <div>
                        <label for="accessory_other">Sonstiges</label>
                        <input id="accessory_other" name="accessory_other" value="{{ old('accessory_other', $ticket->customerMachineProfile?->accessory_other) }}" form="ticket-edit-form">
                        @error('accessory_other') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
            @else
                <div>
                    <label>Maschinentyp</label>
                    <input value="{{ $ticket->customerMachine?->displayName() ?: (($ticket->customerMachineProfile?->manufacturer_snapshot ? $ticket->customerMachineProfile->manufacturer_snapshot.' / ' : '').($ticket->customerMachineProfile?->machine_ref_snapshot ?: '-')) }}" readonly>
                </div>
                <div>
                    <label>Seriennummer</label>
                    <input value="{{ $ticket->customerMachineProfile?->serial_number ?: $ticket->customerMachine?->serial_number ?: '-' }}" readonly>
                </div>

                <div>
                    <label>Zubehör</label>
                    <p>{{ $ticket->customerMachineProfile?->accessoriesSummary() ?: 'Keine Einträge' }}</p>
                </div>

                @if ($ticket->customerMachineProfile?->warranty_claimed)
                    <div class="alert alert-info">
                        ✓ Garantie wird beansprucht
                    </div>
                @endif
            @endif
        </div>

    <!-- Fehlerbeschreibung & Annahmenotiz -->
    <div class="panel panel-body stack">
        <h3>Fehlerbeschreibung</h3>

        @if ($isEditable)
            <form id="ticket-edit-form" method="post" action="{{ route('geiser-portal.tickets.update', $ticket) }}" class="stack" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="error_description">Fehlerbeschreibung *</label>
                    <textarea id="error_description" name="error_description" required style="min-height: 120px;">{{ old('error_description', $ticket->error_description) }}</textarea>
                    @error('error_description') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-2">
                    <div>
                        <label for="contact_name">Ansprechpartner *</label>
                        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot) }}" required>
                        @error('contact_name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="email">E-Mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $ticket->customerMachineProfile?->email ?: $ticket->customer_email_snapshot) }}">
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-2">
                    <div>
                        <label for="phone">Telefon</label>
                        <input id="phone" name="phone" value="{{ old('phone', $ticket->customerMachineProfile?->phone) }}">
                    </div>
                    <div></div>
                </div>

                <div class="grid grid-2">
                    <div>
                        <label for="street">Straße</label>
                        <input id="street" name="street" value="{{ old('street', $ticket->customerMachineProfile?->street) }}">
                    </div>
                    <div>
                        <label for="zip">PLZ</label>
                        <input id="zip" name="zip" value="{{ old('zip', $ticket->customerMachineProfile?->zip) }}" style="max-width: 150px;">
                    </div>
                </div>

                <div>
                    <label for="city">Stadt</label>
                    <input id="city" name="city" value="{{ old('city', $ticket->customerMachineProfile?->city) }}">
                    @error('city') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-2">
                    <div>
                        <label for="repair_approval_limit">Reparatur ohne Ruecksprache bis EUR</label>
                        <input id="repair_approval_limit" name="repair_approval_limit" type="number" step="0.01" min="0" value="{{ old('repair_approval_limit', $ticket->customerMachineProfile?->repair_approval_limit) }}">
                        @error('repair_approval_limit') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="intake_note">Zusatzinfo</label>
                        <input id="intake_note" name="intake_note" value="{{ old('intake_note', $ticket->customerMachineProfile?->intake_note) }}">
                        @error('intake_note') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-top: 8px;">
                    <label class="check-row">
                        <input type="checkbox" name="machine_returned" value="1" @checked(old('machine_returned', $ticket->machine_returned)) form="ticket-edit-form">
                        Maschine ausgegeben
                    </label>
                </div>

                <button class="btn" type="submit" form="ticket-edit-form">Änderungen speichern</button>
            </form>
        @else
            <div>
                <p>{{ nl2br(e($ticket->error_description)) }}</p>
            </div>

            <form method="post" action="{{ route('geiser-portal.tickets.machine-returned', $ticket) }}" style="margin-top: 1rem;">
                @csrf
                @method('PUT')
                <label class="check-row">
                    <input type="checkbox" name="machine_returned" value="1" @checked(old('machine_returned', $ticket->machine_returned)) onchange="this.form.submit()">
                    Maschine ausgegeben
                </label>
            </form>

            @if ($ticket->machine_returned)
                <div class="alert alert-success" style="margin-top: 1rem;">
                    ✓ Maschine wurde ausgegeben
                </div>
            @endif
            
            <h3 style="margin-top: 1.5rem;">Kontaktinformationen</h3>
            <div class="grid grid-2">
                <div>
                    <label>Ansprechpartner</label>
                    <input value="{{ $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot }}" readonly>
                </div>
                <div>
                    <label>E-Mail</label>
                    <input value="{{ $ticket->customerMachineProfile?->email ?: $ticket->customer_email_snapshot }}" readonly>
                </div>
            </div>
            <div class="grid grid-2">
                <div>
                    <label>Telefon</label>
                    <input value="{{ $ticket->customerMachineProfile?->phone ?: '-' }}" readonly>
                </div>
                <div></div>
            </div>
            <div class="grid grid-2">
                <div>
                    <label>Straße</label>
                    <input value="{{ $ticket->customerMachineProfile?->street ?: '-' }}" readonly>
                </div>
                <div>
                    <label>PLZ</label>
                    <input value="{{ $ticket->customerMachineProfile?->zip ?: '-' }}" readonly>
                </div>
            </div>
            <div>
                <label>Stadt</label>
                <input value="{{ $ticket->customerMachineProfile?->city ?: '-' }}" readonly>
            </div>
        @endif

        @if (($ticket->customerMachineProfile?->intake_note) && !$isEditable)
            <h3 style="margin-top: 2rem;">Annahmenotiz</h3>
            <div>
                <p>{{ nl2br(e($ticket->customerMachineProfile->intake_note)) }}</p>
            </div>
        @endif

        <h3 style="margin-top: 2rem;">Techniker-Info zur Reparatur</h3>
        <div>
            @if ($ticket->technician_note)
                <p>{!! nl2br(e($ticket->technician_note)) !!}</p>
            @else
                <p>-</p>
            @endif
        </div>
    </div>
    </div>

    <!-- Parts & ServiceLines vom Hauptportal -->
    @if ($ticket->parts->isNotEmpty() || $ticket->serviceLines->isNotEmpty())
        <div class="panel panel-body stack">
            <h3>Interne Positionen (aus Bearbeitung)</h3>
            
            @if ($ticket->serviceLines->isNotEmpty())
                <div>
                    <h4>Leistungen</h4>
                    <div class="table-wrap">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Leistung</th>
                                    <th style="text-align: right;">Menge</th>
                                    <th style="text-align: right;">Preis je Einheit</th>
                                    <th style="text-align: right;">Summe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ticket->serviceLines as $line)
                                    <tr>
                                        <td>{{ $line->label_snapshot }}</td>
                                        <td style="text-align: right;">{{ number_format($line->quantity, 2, ',', '.') }}</td>
                                        <td style="text-align: right;">{{ number_format($line->sales_price_snapshot, 2, ',', '.') }} €</td>
                                        <td style="text-align: right;"><strong>{{ number_format((float)$line->quantity * (float)$line->sales_price_snapshot, 2, ',', '.') }} €</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($ticket->parts->isNotEmpty())
                <div style="margin-top: 1.5rem;">
                    <h4>Ersatzteile</h4>
                    <div class="table-wrap">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Artikelnummer</th>
                                    <th>Bezeichnung</th>
                                    <th style="text-align: right;">Menge</th>
                                    <th style="text-align: right;">Preis je Einheit</th>
                                    <th style="text-align: right;">Summe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ticket->parts as $part)
                                    <tr>
                                        <td>{{ $part->part_ref_snapshot }}</td>
                                        <td>{{ $part->label_snapshot }}</td>
                                        <td style="text-align: right;">{{ number_format($part->quantity, 2, ',', '.') }} {{ $part->unit_snapshot }}</td>
                                        <td style="text-align: right;">{{ number_format($part->sales_price_snapshot, 2, ',', '.') }} €</td>
                                        <td style="text-align: right;"><strong>{{ number_format($part->totalNet(), 2, ',', '.') }} €</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Reparaturzustimmung & Kostenvoranschlag (read-only oder edit) -->
    <div class="panel panel-body stack">
        <h3>Kostenzustimmung & Kostenvoranschlag</h3>

        @if ($isEditable)
            <p class="muted">Das Genehmigungslimit kann oben im Bearbeitungsformular angepasst werden.</p>

            <h4>Voraussichtliche interne Kosten</h4>
            <div class="stack" style="gap: 1rem;">

                    @foreach ($estimateLines as $line)
                        <div class="grid" style="grid-template-columns: 1fr 2fr auto auto;">
                            <div>
                                <label for="{{ $line['key'] }}">{{ $line['label'] }}</label>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                                <input id="{{ $line['key'] }}" name="{{ $line['key'] }}" type="number" step="1" min="0" value="{{ old($line['key'], $line['quantity']) }}" style="flex: 1;" form="ticket-edit-form">
                                <span style="white-space: nowrap; color: #666; font-size: 0.9em;">
                                    × {{ number_format($line['unit_price'], 2, ',', '.') }} €
                                </span>
                            </div>
                            <div style="text-align: right; white-space: nowrap;">
                                <strong class="line-total">{{ number_format($line['line_total'], 2, ',', '.') }} €</strong>
                            </div>
                        </div>
                        <p class="muted" style="margin: -0.8rem 0 0 0;">{{ $line['hint'] }}</p>
                    @endforeach
            </div>

            <div style="border-top: 1px solid #ddd; padding-top: 1rem; margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong>Gesamtbetrag:</strong>
                    <strong class="total-estimate" style="font-size: 1.2em;">
                        {{ number_format($estimateTotal ?? 0, 2, ',', '.') }} €
                    </strong>
                </div>
            </div>
        @else
                <div>
                    <label>Genehmigungslimit</label>
                    <input value="{{ $ticket->customerMachineProfile?->repair_approval_limit ? number_format($ticket->customerMachineProfile->repair_approval_limit, 2, ',', '.') . ' €' : '-' }}" readonly>
                </div>

                <h4>Voraussichtliche interne Kosten</h4>
                <div class="table-wrap">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th style="text-align: right;">Menge</th>
                                <th style="text-align: right;">Einheit</th>
                                <th style="text-align: right;">Gesamtbetrag</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estimateLines as $line)
                                <tr>
                                    <td>
                                        <strong>{{ $line['label'] }}</strong>
                                        <br><span style="color: #666; font-size: 0.9em;">{{ $line['hint'] }}</span>
                                    </td>
                                    <td style="text-align: right;">{{ number_format($line['quantity'], 2, ',', '.') }}</td>
                                    <td style="text-align: right;">{{ number_format($line['unit_price'], 2, ',', '.') }} €</td>
                                    <td style="text-align: right;"><strong>{{ number_format($line['line_total'], 2, ',', '.') }} €</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid #ddd;">
                                <td colspan="3" style="text-align: right;"><strong>Summe:</strong></td>
                                <td style="text-align: right;"><strong>{{ number_format($estimateTotal ?? 0, 2, ',', '.') }} €</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
        @endif
    </div>

    <div class="panel panel-body stack">
        <div class="section-title">
            <h3>Nachrichtenverlauf</h3>
        </div>

        @if ($ticket->messages->isEmpty())
            <p class="muted">Noch keine Nachrichten vorhanden.</p>
        @else
            <div class="message-thread">
                @foreach ($ticket->messages as $ticketMessage)
                    <article class="message-item {{ $ticketMessage->sender_type === \App\Models\TicketMessage::SENDER_CUSTOMER ? 'is-customer' : 'is-admin' }}">
                        <header class="message-meta">
                            <strong>{{ $ticketMessage->sender_label ?: ($ticketMessage->sender_type === \App\Models\TicketMessage::SENDER_CUSTOMER ? 'Il Coccolino' : 'Service') }}</strong>
                            <span class="muted">{{ $ticketMessage->created_at?->format('d.m.Y H:i') }}</span>
                        </header>
                        @if (filled($ticketMessage->body))
                            <div class="message-body">{{ $ticketMessage->body }}</div>
                        @endif
                        @if ($ticketMessage->attachments->isNotEmpty())
                            <div class="message-attachments">
                                @foreach ($ticketMessage->attachments as $attachment)
                                    <a href="{{ route('geiser-portal.tickets.messages.attachments.download', [$ticket, $ticketMessage, $attachment]) }}" class="btn secondary">
                                        Anhang: {{ $attachment->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('geiser-portal.tickets.messages.store', $ticket) }}" class="stack" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="reply-body">Antwort</label>
                <textarea id="reply-body" name="body" placeholder="Ihre Nachricht an den Service...">{{ old('body') }}</textarea>
                @error('body')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div>
                <label for="reply-attachments">Fotos/Scans (optional)</label>
                <input id="reply-attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                @error('attachments')<span class="error">{{ $message }}</span>@enderror
                @error('attachments.*')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="button-row">
                <button class="btn" type="submit">Antworten</button>
            </div>
        </form>
    </div>

    @if (!$isEditable)
        <div class="alert alert-info">
            <strong>ℹ Hinweis:</strong> Dieses Ticket ist nicht mehr offen. Aenderungen sind nicht moeglich. Sollten Sie Aenderungen benoetigen, kontaktieren Sie bitte den Support.
        </div>
    @endif

    <script>
        @if ($isEditable)
        // Live calculator for estimates
        document.querySelectorAll('input[type="number"][name^="estimate_"]').forEach(input => {
            input.addEventListener('change', updateEstimate);
            input.addEventListener('keyup', updateEstimate);
        });

        function updateEstimate() {
            const lines = {
                'estimate_qty_tech': 16.90,
                'estimate_qty_service_fee': 29.00,
                'estimate_qty_vde': 6.50,
                'estimate_qty_consumables': 4.50
            };

            let total = 0;
            Object.entries(lines).forEach(([fieldName, unitPrice]) => {
                const input = document.querySelector(`input[name="${fieldName}"]`);
                const qty = parseFloat(input?.value || 0) || 0;
                const lineTotal = qty * unitPrice;
                total += lineTotal;

                // Find and update line total
                const formattedTotal = lineTotal.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const lineTotalSpan = input?.parentElement?.parentElement?.querySelector('.line-total');
                if (lineTotalSpan) {
                    lineTotalSpan.textContent = formattedTotal + ' €';
                }
            });

            const totalSpan = document.querySelector('.total-estimate');
            if (totalSpan) {
                totalSpan.textContent = total.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            }
        }
        @endif
    </script>
@endsection

@push('scripts')
<script>
// Camera + compress for show page
(() => {
    const btnCamera   = document.getElementById('btn-camera');
    if (!btnCamera) return;
    const btnFile     = document.getElementById('btn-file');
    const btnRemove   = document.getElementById('btn-remove-photo');
    const inputCamera = document.getElementById('input-camera');
    const inputFile   = document.getElementById('input-file');
    const finalInput  = document.getElementById('customer_photo_final');
    const preview     = document.getElementById('photo-preview');
    const previewWrap = document.getElementById('photo-preview-wrap');
    const filename    = document.getElementById('photo-filename');
    const hasExistingPhoto = {{ $ticket->customer_photo_path ? 'true' : 'false' }};
    btnCamera.addEventListener('click', () => inputCamera.click());
    btnFile.addEventListener('click',   () => inputFile.click());
    inputCamera.addEventListener('change', e => handleFile(e.target.files[0]));
    inputFile.addEventListener('change',   e => handleFile(e.target.files[0]));
    btnRemove.addEventListener('click', () => {
        preview.src = '';
        previewWrap.style.display = 'none';
        filename.textContent = '';
        const dt = new DataTransfer();
        finalInput.files = dt.files;
        setRequiredFields(!hasExistingPhoto);
    });
    function handleFile(file) {
        if (!file) return;
        compressImage(file, 1200, 0.75, (blob, name) => {
            const dt = new DataTransfer();
            dt.items.add(new File([blob], name, { type: 'image/jpeg' }));
            finalInput.files = dt.files;
            preview.src = URL.createObjectURL(blob);
            previewWrap.style.display = 'block';
            filename.textContent = name + ' (' + Math.round(blob.size/1024) + ' KB)';
            setRequiredFields(false);
        });
    }
    function setRequiredFields(required) {
        ['serial_number', 'machine_ref_snapshot', 'contact_name', 'error_description'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (required) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }
    function compressImage(file, maxDim, quality, callback) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                let w=img.width, h=img.height;
                if (w>maxDim||h>maxDim) { if(w>h){h=Math.round(h*maxDim/w);w=maxDim;}else{w=Math.round(w*maxDim/h);h=maxDim;} }
                const canvas=document.createElement('canvas'); canvas.width=w; canvas.height=h;
                canvas.getContext('2d').drawImage(img,0,0,w,h);
                canvas.toBlob(blob => callback(blob, file.name.replace(/\.[^.]+$/, '')+'_compressed.jpg'), 'image/jpeg', quality);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    setRequiredFields(!hasExistingPhoto);
})();
</script>
<script>
// photo-compress included via layout
</script>
@endpush
