<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>TH Services Rechnung {{ $ticket->ticket_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111827; }
        h1 { margin: 0 0 6px; font-size: 16px; }
        h2 { margin: 14px 0 6px; font-size: 12px; }
        p { margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .grid td { width: 50%; vertical-align: top; padding: 2px 8px 2px 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        .table th { background: #f3f4f6; }
        .amount { text-align: right; white-space: nowrap; }
        .section { margin-top: 12px; }
        .vat-note { margin-top: 10px; padding: 6px 8px; border: 1px solid #d1d5db; background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Rechnung</h1>
    <p class="muted">TH Services &amp; Solutions Beleg | erstellt am {{ $createdAt->format('d.m.Y H:i') }}</p>

    <table class="grid">
        <tr>
            <td>
                <h2>Absender</h2>
                <p><strong>{{ $sender['company_name'] ?? '' }}</strong></p>
                @if (!empty($sender['contact_name']))
                    <p>{{ $sender['contact_name'] }}</p>
                @endif
                <p>{{ $sender['address_line_1'] ?? '' }}</p>
                @if (!empty($sender['address_line_2']))
                    <p>{{ $sender['address_line_2'] }}</p>
                @endif
                @if (!empty($sender['country']))
                    <p>{{ $sender['country'] }}</p>
                @endif
            </td>
            <td>
                <h2>Rechnung an</h2>
                <p><strong>{{ $invoiceRecipient['name'] ?: $ticket->customer_name_snapshot }}</strong></p>
                <p>{{ $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot ?: '-' }}</p>
                @if (!empty($invoiceRecipient['address']))
                    <p>{!! nl2br(e($invoiceRecipient['address'])) !!}</p>
                @endif
                @if (!empty($invoiceRecipient['zip']) || !empty($invoiceRecipient['town']))
                    <p>{{ trim(($invoiceRecipient['zip'] ?? '').' '.($invoiceRecipient['town'] ?? '')) }}</p>
                @endif
                @if (!empty($invoiceRecipient['state']) || !empty($invoiceRecipient['country']))
                    <p>{{ collect([$invoiceRecipient['state'] ?? null, $invoiceRecipient['country'] ?? null])->filter()->implode(', ') }}</p>
                @endif
                <p>E-Mail: {{ $invoiceRecipient['email'] ?: ($ticket->customerMachineProfile?->email ?: $ticket->customer_email_snapshot ?: '-') }}</p>
                @if (!empty($invoiceRecipient['code_client']))
                    <p>Kundennummer: {{ $invoiceRecipient['code_client'] }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>Belegdaten</h2>
        <table class="grid">
            <tr>
                <td>Rechnungsnummer (intern): <strong>THS-{{ $ticket->ticket_number }}</strong></td>
                <td>Ticket: <strong>{{ $ticket->ticket_number }}</strong></td>
            </tr>
            <tr>
                <td>Maschine:
                    <strong>
                        {{ trim(($ticket->customerMachine?->manufacturer_snapshot ?: $ticket->customerMachineProfile?->manufacturer_snapshot ?: '').' '.($ticket->customerMachine?->machine_ref_snapshot ?: $ticket->customerMachineProfile?->machine_ref_snapshot ?: '')) ?: '-' }}
                    </strong>
                </td>
                <td>Seriennummer: <strong>{{ $ticket->customerMachine?->serial_number ?: $ticket->customerMachineProfile?->serial_number ?: '-' }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Leistungspositionen</h2>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%;">Typ</th>
                    <th style="width: 12%;">Ref</th>
                    <th>Leistung / Artikel</th>
                    <th style="width: 9%;" class="amount">Menge</th>
                    <th style="width: 14%;" class="amount">Einzelpreis</th>
                    <th style="width: 14%;" class="amount">Gesamt</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoiceLines as $line)
                    <tr>
                        <td>{{ $line['type'] }}</td>
                        <td>{{ $line['reference'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="amount">{{ number_format((float) $line['quantity'], 2, ',', '.') }}</td>
                        <td class="amount">{{ number_format((float) $line['quantity'] > 0 ? $line['line_net_after_discount'] / $line['quantity'] : 0, 2, ',', '.') }} EUR</td>
                        <td class="amount"><strong>{{ number_format((float) $line['line_net_after_discount'], 2, ',', '.') }} EUR</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Keine Positionen vorhanden.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="amount"><strong>Rechnungsbetrag (netto)</strong></td>
                    <td class="amount"><strong>{{ number_format($totalNet, 2, ',', '.') }} EUR</strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="amount"><strong>Rechnungsbetrag (brutto)</strong></td>
                    <td class="amount"><strong>{{ number_format($totalNet, 2, ',', '.') }} EUR</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="vat-note">
        <strong>{{ $vatNote }}</strong>
    </div>

    <div class="section">
        <h2>Kontoverbindung</h2>
        <p><strong>Kontoinhaber:</strong> {{ $bank['account_holder'] ?? '' }}</p>
        <p><strong>Bank:</strong> {{ $bank['bank_name'] ?? '' }}</p>
        <p><strong>Bankleitzahl:</strong> {{ $bank['blz'] ?? '' }}</p>
        <p><strong>Kontonummer:</strong> {{ $bank['account_number'] ?? '' }}</p>
        <p><strong>IBAN:</strong> {{ $bank['iban'] ?? '' }}</p>
        <p><strong>BIC / SWIFT-Code:</strong> {{ $bank['bic'] ?? '' }}</p>
    </div>
</body>
</html>
