# Datei: resources\views\customer-portal-geiser\dashboard.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\customer-portal-geiser\dashboard.blade.php`
- **Stand:** 2026-06-28 08:47:07
- **Typ:** blade

## Code

```blade
@extends('layouts.customer-portal-geiser')

@section('content')
    <div class="page-header">
        <div>
            <h1>Meine Tickets</h1>
            <p class="muted">{{ $account->company_name }} @if ($account->dolibarr_customer_code) Â· Kundennummer {{ $account->dolibarr_customer_code }} @endif Â· Dolibarr-ID {{ $account->dolibarr_thirdparty_id }}</p>
        </div>
        <a class="btn" href="{{ route('geiser-portal.tickets.create') }}">Neues Ticket erfassen</a>
    </div>

    <div class="panel panel-body stack">
        <div class="grid grid-2">
            <div>
                <label>Fester Kunde aus Dolibarr</label>
                <input value="{{ $account->company_name }}" readonly>
            </div>
            <div>
                <label>Kundennummer</label>
                <input value="{{ $account->dolibarr_customer_code ?: 'ID '.$account->dolibarr_thirdparty_id }}" readonly>
            </div>
        </div>
    </div>

    <div class="panel panel-body">
        @if ($tickets->isEmpty())
            <p class="muted">Es wurden noch keine Tickets ueber das Geiser-Serviceportal erstellt.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Status</th>
                        <th>Seriennummer</th>
                        <th>Maschine</th>
                        <th>Ansprechpartner</th>
                        <th>Erstellt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('geiser-portal.tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                                @if (!$ticket->created_via_customer_portal)
                                    <span class="badge bg-info">vom Techniker erstellt</span>
                                @endif
                            </td>
                            <td>{{ $customerStatusLabels[$ticket->id] ?? $ticket->statusLabel() }}</td>
                            <td>{{ $ticket->customerMachineProfile?->serial_number ?: $ticket->customerMachine?->serial_number ?: '-' }}</td>
                            <td>{{ $ticket->customerMachine?->displayName() }}</td>
                            <td>{{ $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot ?: '-' }}</td>
                            <td>{{ $ticket->created_at?->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

```
