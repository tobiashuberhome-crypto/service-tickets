<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Arbeitsbericht {{ $ticket->ticket_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 20px 28px;
        }
        h1 { margin: 0 0 4px; font-size: 18px; }
        h2 { margin: 16px 0 6px; font-size: 12px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        p { margin: 0 0 3px; }
        .muted { color: #6b7280; }
        .machine-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            margin-top: 8px;
        }
        .machine-box p { margin: 0 0 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 11px; }
        .pos { width: 8%; text-align: center; }
        .copy-block { white-space: pre-wrap; font-size: 9px; line-height: 1.5; }
        .empty { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <h1>Arbeitsbericht</h1>
    <p class="muted">Ticket: <strong>{{ $ticket->ticket_number }}</strong> &nbsp;|&nbsp; Erstellt am: {{ $generatedAt->format('d.m.Y H:i') }} Uhr</p>

    <div class="machine-box">
        <h2>Maschinendaten</h2>
        <p><strong>Hersteller:</strong> {{ $manufacturer ?: '-' }}</p>
        <p><strong>Maschinen-Typ / Modell:</strong> {{ $machineRef ?: '-' }}</p>
        <p><strong>Seriennummer:</strong> {{ $serialNumber }}</p>
    </div>

    <h2>Arbeitsbeschreibung</h2>
    <table>
        <thead>
            <tr>
                <th class="pos">Pos.</th>
                <th>Beschreibung der durchgefuehrten Arbeiten</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoiceLines as $index => $line)
                <tr>
                    <td class="pos">{{ $index + 1 }}</td>
                    <td><div class="copy-block">{{ $line['copy_text'] }}</div></td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="empty">Noch keine Arbeitspositionen vorhanden.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
