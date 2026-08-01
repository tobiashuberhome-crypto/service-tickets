@extends('pdf.layouts.base')

@section('title', 'Il Coccolino Ticket '.$ticket->ticket_number)
@section('subtitle', 'Il Coccolino-Serviceportal · Ticket '.$ticket->ticket_number)

@section('content')
    @php
        $profile = $ticket->customerMachineProfile;
        $machine = $ticket->customerMachine;
        $estimateRows = collect($estimateLines);
    @endphp

    <h1>Ticket {{ $ticket->ticket_number }}</h1>
    <table class="grid">
        <tr>
            <td><strong>Status:</strong> {{ $ticket->statusLabel() }}</td>
            <td><strong>Annahme:</strong> {{ optional($ticket->acceptance_date)->format('d.m.Y') ?: '-' }}</td>
            <td><strong>Erstellt:</strong> {{ optional($ticket->created_at)->format('d.m.Y H:i') ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Kunde:</strong> {{ $ticket->customer_name_snapshot }}</td>
            <td><strong>Ansprechpartner:</strong> {{ $ticket->customer_contact_name_snapshot ?: '-' }}</td>
            <td><strong>E-Mail:</strong> {{ $ticket->customer_email_snapshot ?: '-' }}</td>
        </tr>
    </table>

    <h2>Maschinendaten</h2>
    <table class="grid">
        <tr>
            <td><strong>Hersteller:</strong> {{ $machine?->manufacturer_snapshot ?: '-' }}</td>
            <td><strong>Modell / Typ:</strong> {{ $machine?->machine_ref_snapshot ?: '-' }}</td>
            <td><strong>Seriennummer:</strong> {{ $profile?->serial_number ?: $machine?->serial_number ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Garantie:</strong> {{ $profile?->warranty_claimed ? 'Ja' : 'Nein' }}</td>
            <td colspan="2"><strong>Zubehoer:</strong> {{ $profile?->accessoriesSummary() ?: '-' }}</td>
        </tr>
    </table>

    <h2>Fehlerbeschreibung</h2>
    <div style="border:1px solid #e5e7eb; padding:8px 10px;">
        {!! nl2br(e($ticket->error_description ?: '-')) !!}
    </div>

    @if ($estimateRows->isNotEmpty())
        <h2>Kostenschaetzung</h2>
        <table>
            <thead>
            <tr>
                <th>Position</th>
                <th>Hinweis</th>
                <th class="align-right">Menge</th>
                <th class="align-right">Einheit</th>
                <th class="align-right">Gesamt</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($estimateRows as $line)
                <tr>
                    <td>{{ $line['label'] ?? '-' }}</td>
                    <td>{{ $line['hint'] ?? '-' }}</td>
                    <td class="align-right">{{ number_format((float) ($line['quantity'] ?? 0), 2, ',', '.') }}</td>
                    <td class="align-right">{{ number_format((float) ($line['unit_price'] ?? 0), 2, ',', '.') }} EUR</td>
                    <td class="align-right">{{ number_format((float) ($line['line_total'] ?? 0), 2, ',', '.') }} EUR</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4" class="align-right">Gesamtsumme</th>
                <th class="align-right">{{ number_format((float) ($ticket->customer_portal_estimate_total ?? 0), 2, ',', '.') }} EUR</th>
            </tr>
            </tfoot>
        </table>
    @endif

    <h2>Genehmigungslimit</h2>
    <div style="border:1px solid #e5e7eb; padding:8px 10px;">
        @if ($profile?->repair_approval_limit !== null)
            {{ number_format((float) $profile->repair_approval_limit, 2, ',', '.') }} EUR
        @else
            Kein Limit hinterlegt
        @endif
    </div>

    @if ($profile?->intake_note)
        <h2>Annahmenotiz</h2>
        <div style="border:1px solid #e5e7eb; padding:8px 10px;">
            {!! nl2br(e($profile->intake_note)) !!}
        </div>
    @endif
@endsection
