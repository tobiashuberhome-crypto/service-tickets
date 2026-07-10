# Datei: resources\views\tickets\delivery-note.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\tickets\delivery-note.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Lieferschein</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        .muted { color: #6b7280; }
        .box { margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Lieferschein</h1>
        <div class="muted">Erstellt am: {{ $createdAt->format('d.m.Y H:i') }}</div>
        <div class="muted">Anzahl Tickets: {{ $tickets->count() }}</div>
    </div>

    @foreach ($tickets as $ticket)
        <div class="box">
            <strong>Ticket: {{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</strong><br>
            Kunde: {{ $ticket->customer_name_snapshot }}<br>
            Maschine: {{ $ticket->customerMachine?->manufacturer_snapshot }} / {{ $ticket->customerMachine?->machine_ref_snapshot }}<br>
            @if ($ticket->customerMachine?->serial_number)
                Seriennummer: {{ $ticket->customerMachine->serial_number }}<br>
            @endif

            <table>
                <thead>
                    <tr>
                        <th style="width: 18%;">Typ</th>
                        <th>Bezeichnung</th>
                        <th style="width: 12%;">Menge</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ticket->serviceLines as $line)
                        <tr>
                            <td>Leistung</td>
                            <td>{{ $line->label_snapshot }}</td>
                            <td>{{ number_format((float) $line->quantity, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                    @endforelse

                    @foreach ($ticket->parts as $part)
                        <tr>
                            <td>Ersatzteil</td>
                            <td>{{ $part->part_ref_snapshot }} - {{ $part->label_snapshot }}</td>
                            <td>{{ number_format((float) $part->quantity, 3, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    @if ($ticket->serviceLines->isEmpty() && $ticket->parts->isEmpty())
                        <tr>
                            <td colspan="3">Keine Positionen vorhanden.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>


```
