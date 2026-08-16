@php
/**
 * Data: $tickets (collection)
 */
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lieferschein</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:12px }
        table { width:100%; border-collapse: collapse; margin-top:12px }
        th, td { border: 1px solid #ddd; padding:8px; }
        th { background:#f5f5f5 }
        .signature { margin-top:40px }
    </style>
</head>
<body>
    <h2>Lieferschein</h2>
    <p>Erstellt: {{ $generated_at->format('d.m.Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Ticket-Nr</th>
                <th>Seriennummer</th>
                <th>Hersteller</th>
                <th>Maschine</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->ticket_number }}</td>
                    <td>{{ $ticket->customerMachine?->serial_number ?? '-' }}</td>
                    <td>{{ $ticket->customerMachine?->manufacturer_snapshot ?? '-' }}</td>
                    <td>{{ $ticket->customerMachine?->displayName() ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <p>Unterschrift Empfänger: ___________________________________________</p>
    </div>
</body>
</html>