<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $ticket->ticket_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; line-height: 1.25; }
        h1 { margin: 0 0 4px; font-size: 14px; }
        h2 { margin: 12px 0 8px; font-size: 12px; }
        p { margin: 0 0 6px; }
        .muted { color: #6b7280; }
        .section { margin-top: 12px; }
        .grid-2 { width: 100%; border-collapse: collapse; }
        .grid-2 td { width: 50%; vertical-align: top; padding: 2px 8px 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .signature { margin-top: 34px; }
        .signature-line { margin-top: 42px; border-top: 1px solid #111827; width: 320px; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Reparaturticket</h1>
    <p class="muted">Ticket: {{ $ticket->ticket_number }} | Erstellt: {{ $generatedAt->format('d.m.Y H:i') }}</p>
    <p class="muted">Status: {{ $ticket->statusLabel() }}</p>

    <div class="section">
        <h2>Kunde</h2>
        <table class="grid-2">
            <tr>
                <td><strong>Firma:</strong> {{ $ticket->customer_name_snapshot }}</td>
                <td><strong>Ansprechpartner:</strong> {{ $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>E-Mail:</strong> {{ $ticket->customerMachineProfile?->email ?: $ticket->customer_email_snapshot ?: '-' }}</td>
                <td><strong>Telefon:</strong> {{ $ticket->customerMachineProfile?->phone ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Maschine</h2>
        <table class="grid-2">
            <tr>
                <td><strong>Hersteller:</strong> {{ $ticket->customerMachineProfile?->manufacturer_snapshot ?: $ticket->customerMachine?->manufacturer_snapshot ?: '-' }}</td>
                <td><strong>Typ / Modell:</strong> {{ $ticket->customerMachineProfile?->machine_ref_snapshot ?: $ticket->customerMachine?->machine_ref_snapshot ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>Seriennummer:</strong> {{ $ticket->customerMachineProfile?->serial_number ?: $ticket->customerMachine?->serial_number ?: '-' }}</td>
                <td><strong>Annahme-Datum:</strong> {{ optional($ticket->acceptance_date)->format('d.m.Y') ?: '-' }}</td>
            </tr>
        </table>
        <p><strong>Foto vorhanden:</strong> {{ $ticket->customer_photo_path ? 'Ja' : 'Nein' }}</p>
    </div>

    @if ($ticket->customerMachineProfile)
        <div class="section">
            <h2>Annahme / Zubehoer</h2>
            <p><strong>Garantie beansprucht:</strong> {{ $ticket->customerMachineProfile->warranty_claimed ? 'Ja' : 'Nein' }}</p>
            <p><strong>Zubehoer:</strong> {{ $ticket->customerMachineProfile->accessoriesSummary() ?: 'Keine Eintraege' }}</p>
            <p><strong>Sonstiges Zubehoer:</strong> {{ $ticket->customerMachineProfile->accessory_other ?: '-' }}</p>
            <p><strong>Reparatur ohne Ruecksprache bis:</strong>
                @if ($ticket->customerMachineProfile->repair_approval_limit !== null)
                    {{ number_format((float) $ticket->customerMachineProfile->repair_approval_limit, 2, ',', '.') }} €
                @else
                    -
                @endif
            </p>
        </div>
    @endif

    <div class="section">
        <h2>Fehlerbeschreibung</h2>
        <p>{!! nl2br(e($ticket->error_description ?: '-')) !!}</p>
    </div>

    @if ($ticket->customerMachineProfile?->intake_note)
        <div class="section">
            <h2>Zusatzinfo / Annahmenotiz</h2>
            <p>{!! nl2br(e($ticket->customerMachineProfile->intake_note)) !!}</p>
        </div>
    @endif

    <div class="section">
        <h2>Positionen</h2>
        <table>
            <thead>
                <tr>
                    <th>Typ</th>
                    <th>Ref</th>
                    <th>Bezeichnung</th>
                    <th style="text-align:right;">Menge</th>
                    <th style="text-align:right;">Einzelpreis</th>
                    <th style="text-align:right;">Gesamt</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoiceSummary['invoiceLines'] as $line)
                    <tr>
                        <td>{{ $line['type'] }}</td>
                        <td>{{ $line['reference'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td style="text-align:right;">{{ number_format((float) $line['quantity'], 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format((float) $line['unit_price'], 2, ',', '.') }} €</td>
                        <td style="text-align:right;"><strong>{{ number_format((float) $line['discounted_total'], 2, ',', '.') }} €</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Noch keine Positionen erfasst.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;"><strong>Gesamtsumme (brutto)</strong></td>
                    <td style="text-align:right;"><strong>{{ number_format((float) $invoiceSummary['totalGross'], 2, ',', '.') }} €</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="signature">
        <div class="signature-line">Datum, Unterschrift Kunde</div>
    </div>

    @include('partials.ticket-print-footer-note')
</body>
</html>
