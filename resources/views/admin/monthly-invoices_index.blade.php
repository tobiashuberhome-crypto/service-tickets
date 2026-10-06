@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Monatsrechnungen</h1>
    <p class="muted">Verwaltung aller generierten Monatsrechnungen und ihrer enthaltenen Tickets</p>
</div>

<div class="panel panel-body">
    <form method="get" class="filter-bar" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <select name="portal_scope" class="form-control" style="width: auto;">
            <option value="">Alle Portale</option>
            <option value="geiser" @selected(request('portal_scope') === 'geiser')>Geiser</option>
            <option value="cibena" @selected(request('portal_scope') === 'cibena')>Cibena</option>
            <option value="school" @selected(request('portal_scope') === 'school')>Schule</option>
            <option value="default" @selected(request('portal_scope') === 'default')>Standard</option>
        </select>
        <input type="number" name="customer_id" placeholder="Kunde-ID" value="{{ request('customer_id') }}" class="form-control" style="width: 150px;">
        <input type="number" name="year" placeholder="Jahr" value="{{ request('year') }}" min="2000" max="2099" class="form-control" style="width: 120px;">
        <button type="submit" class="btn">Filtern</button>
        @if(request()->hasAny(['portal_scope', 'customer_id', 'year']))
            <a href="{{ route('monthly-invoices.index') }}" class="btn secondary">Zurücksetzen</a>
        @endif
    </form>
</div>

@if($invoices->isEmpty())
    <div class="panel panel-body">
        <p class="muted">Keine Monatsrechnungen gefunden.</p>
    </div>
@else
    <div class="panel panel-body" style="overflow-x: auto;">
        <table style="width: 100%;">
            <thead>
            <tr>
                <th>Rechnung</th>
                <th>Portal</th>
                <th>Kunde-ID</th>
                <th>Zeitraum</th>
                <th>Tickets</th>
                <th>Generiert</th>
                <th>Aktion</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>
                        <strong>{{ $invoice->invoice_number }}</strong>
                    </td>
                    <td>
                        <span class="badge" style="background: #0066cc; color: #fff; font-size: 0.85em;">
                            {{ $invoice->portal_scope }}
                        </span>
                    </td>
                    <td><code>{{ $invoice->dolibarr_customer_id }}</code></td>
                    <td>
                        {{ \DateTime::createFromFormat('!m', (string) $invoice->invoice_month)->format('F') }}
                        {{ $invoice->invoice_year }}
                        @if($invoice->sequence_number > 1)
                            <span style="font-size: 0.85em; color: #666;">(#{{ $invoice->sequence_number }})</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background: #7c3aed; color: #fff;">
                            {{ $invoice->tickets->count() }} Tickets
                        </span>
                    </td>
                    <td style="font-size: 0.85em;">{{ $invoice->generated_at->format('d.m.Y H:i') }}</td>
                    <td>
                        <a href="{{ route('monthly-invoices.show', $invoice) }}" class="btn btn-sm">Details</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">
        {{ $invoices->links() }}
    </div>
@endif
@endsection
