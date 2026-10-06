@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1>Monatsrechnung {{ $invoice->invoice_number }}</h1>
        <p class="muted">
            {{ \DateTime::createFromFormat('!m', (string) $invoice->invoice_month)->format('F') }}
            {{ $invoice->invoice_year }}
            · Portal: {{ $invoice->portal_scope }}
            · Kunde-ID: {{ $invoice->dolibarr_customer_id }}
        </p>
    </div>
    <div class="button-row">
        <a href="{{ route('monthly-invoices.index') }}" class="btn secondary">Zurück</a>
    </div>
</div>

<div class="panel panel-body">
    <div class="grid grid-2">
        <div>
            <label>Rechnungsnummer</label>
            <input value="{{ $invoice->invoice_number }}" readonly>
        </div>
        <div>
            <label>Generiert am</label>
            <input value="{{ $invoice->generated_at->format('d.m.Y H:i:s') }}" readonly>
        </div>
        <div>
            <label>Portal-Bereich</label>
            <input value="{{ $invoice->portal_scope }}" readonly>
        </div>
        <div>
            <label>Kunde-ID (Dolibarr)</label>
            <input value="{{ $invoice->dolibarr_customer_id }}" readonly>
        </div>
    </div>
</div>

<div class="panel panel-body">
    <h2 style="margin-top: 0;">Enthaltene Tickets ({{ $invoice->tickets->count() }})</h2>
    
    @if($invoice->tickets->isEmpty())
        <p class="muted">Keine Tickets in dieser Rechnung enthalten.</p>
    @else
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                <tr>
                    <th>Ticket-Nr.</th>
                    <th>Status</th>
                    <th>Kunde</th>
                    <th>Maschine</th>
                    <th>Seriennummer</th>
                    <th>Erstellt</th>
                    <th>Aktion</th>
                </tr>
                </thead>
                <tbody>
                @foreach($invoice->tickets as $ticket)
                    <tr>
                        <td>
                            <a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                        </td>
                        <td>{{ $ticket->statusLabel() }}</td>
                        <td>{{ $ticket->customer_name_snapshot }}</td>
                        <td>{{ $ticket->customerMachine?->displayName() ?: '-' }}</td>
                        <td>{{ $ticket->customerMachineProfile?->serial_number ?: $ticket->customerMachine?->serial_number ?: '-' }}</td>
                        <td style="font-size: 0.85em;">{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm">Öffnen</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
