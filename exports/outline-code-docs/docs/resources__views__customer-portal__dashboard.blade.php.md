# Datei: resources\views\customer-portal\dashboard.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\customer-portal\dashboard.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.customer-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Meine Tickets</h1>
            <p class="muted">{{ $account->company_name }} @if ($account->dolibarr_customer_code) Â· Kundennummer {{ $account->dolibarr_customer_code }} @endif</p>
        </div>
        <a class="btn" href="{{ route('customer-portal.tickets.create') }}">Neues Ticket erstellen</a>
    </div>

    <div class="panel panel-body">
        @if ($tickets->isEmpty())
            <p class="muted">Es wurden noch keine Tickets ueber das Kundenportal erstellt.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Status</th>
                        <th>Maschine</th>
                        <th>Erstellt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->statusLabel() }}</td>
                            <td>{{ $ticket->customerMachine?->displayName() }}</td>
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
