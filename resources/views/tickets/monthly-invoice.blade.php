<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Monatsrechnung</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; font-size: 12px; } 
        h1 { font-size: 24px; margin-bottom: 8px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .total { margin-top: 20px; text-align: right; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Monatsrechnung</h1>
    <div class="muted">Monat: {{ $monthLabel }} · Erstellt am {{ $createdAt->format('d.m.Y H:i') }}</div>

    <table>
        <thead>
        <tr>
            <th>Ticket</th>
            <th>Kunde</th>
            <th>Maschine</th>
            <th>Seriennummer</th>
            <th>Betrag</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($tickets as $ticket)
            @php
                $summary = $invoiceSummaryByTicket[(string) $ticket->id] ?? ['totalGross' => 0];
                $gross = (float) ($summary['totalGross'] ?? 0);
            @endphp
            <tr>
                <td>{{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</td>
                <td>{{ $ticket->customer_name_snapshot }}</td>
                <td>{{ $ticket->customerMachine?->manufacturer_snapshot }} / {{ $ticket->customerMachine?->machine_ref_snapshot }}</td>
                <td>{{ $ticket->customerMachine?->serial_number ?: '-' }}</td>
                <td>{{ number_format($gross, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total">Gesamtsumme: {{ number_format((float) $monthlyTotalGross, 2, ',', '.') }} €</div>
</body>
</html>
