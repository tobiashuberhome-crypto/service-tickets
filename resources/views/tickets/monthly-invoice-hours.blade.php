<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Stundennachweis {{ $monthLabel }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        p { margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .amount { text-align: right; white-space: nowrap; }
        .total { margin-top: 16px; text-align: right; font-size: 15px; font-weight: bold; }
        .footer-note { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Stundennachweis</h1>
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
        <p class="muted">Monat: {{ $monthLabel }} · Erstellt am {{ $createdAt->format('d.m.Y H:i') }}</p>
    </div>

    <table>
        <thead>
        <tr>
            <th>Ticket</th>
            <th>Kunde</th>
            <th>Maschine</th>
            <th>Seriennummer</th>
            <th class="amount">Stunden</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($tickets as $ticket)
            @php
                $hours = (float) ($hoursByTicket[(string) $ticket->id] ?? 0);
            @endphp
            <tr>
                <td>{{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</td>
                <td>{{ $ticket->customer_name_snapshot }}</td>
                <td>{{ $ticket->customerMachine?->manufacturer_snapshot }} / {{ $ticket->customerMachine?->machine_ref_snapshot }}</td>
                <td>{{ $ticket->customerMachine?->serial_number ?: '-' }}</td>
                <td class="amount">{{ number_format($hours, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total">Gesamtstunden: {{ number_format((float) $monthlyTotalHours, 2, ',', '.') }}</div>

    @if (!empty($footerNote))
        <div class="footer-note">
            <p>{{ $footerNote }}</p>
        </div>
    @endif
</body>
</html>
