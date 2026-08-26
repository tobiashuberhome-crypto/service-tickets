<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Stundennachweis {{ $monthLabel }}</title>
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
    </style>
</head>
<body>
    <h1>Stundennachweis</h1>
    <p class="muted">Il Coccolino Beleg | Monat: {{ $monthLabel }} | erstellt am {{ $createdAt->format('d.m.Y H:i') }}</p>

    <table class="grid">
        <tr>
            <td>
                <h2>Absender</h2>
                <p><strong>{{ $sender['company_name'] ?? '' }}</strong></p>
                <p>{{ $sender['address_line_1'] ?? '' }}</p>
                @if (!empty($sender['address_line_2']))
                    <p>{{ $sender['address_line_2'] }}</p>
                @endif
                @if (!empty($sender['email']))
                    <p>E-Mail: {{ $sender['email'] }}</p>
                @endif
                @if (!empty($sender['phone']))
                    <p>Telefon: {{ $sender['phone'] }}</p>
                @endif
            </td>
            <td>
                <h2>Für</h2>
                <p><strong>{{ $invoiceRecipient['name'] ?: ($tickets->first()->customer_name_snapshot ?? '') }}</strong></p>
                @if (!empty($invoiceRecipient['address']))
                    <p>{!! nl2br(e($invoiceRecipient['address'])) !!}</p>
                @endif
                @if (!empty($invoiceRecipient['zip']) || !empty($invoiceRecipient['town']))
                    <p>{{ trim(($invoiceRecipient['zip'] ?? '').' '.($invoiceRecipient['town'] ?? '')) }}</p>
                @endif
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
                <td>Rechnungsnummer: <strong>{{ $monthlyInvoice->invoice_number }}</strong></td>
                <td>Zeitraum: <strong>{{ $monthLabel }}</strong></td>
            </tr>
            <tr>
                <td colspan="2">Enthaltene Tickets: <strong>{{ $tickets->pluck('ticket_number')->implode(', ') }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Geleistete Arbeitsstunden</h2>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 10%;">Ticket</th>
                    <th style="width: 15%;">Maschine</th>
                    <th style="width: 10%;">Ref</th>
                    <th>Leistung</th>
                    <th style="width: 10%;" class="amount">Stunden</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hoursLines as $line)
                    <tr>
                        <td>{{ $line['ticket_number'] }}</td>
                        <td>{{ $line['machine_label'] }}</td>
                        <td>{{ $line['reference'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="amount">{{ number_format((float) $line['quantity'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Keine Leistungspositionen vorhanden.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="amount"><strong>Gesamtstunden</strong></td>
                    <td class="amount"><strong>{{ number_format((float) $monthlyTotalHours, 2, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if (!empty($footerNote))
        <div class="section">
            <p class="muted">{{ $footerNote }}</p>
        </div>
    @endif
</body>
</html>
