@extends('layouts.customer-portal-cibena')

@section('content')
<div class="page-header">
    <h1>Monatsrechnungen</h1>
    <p class="muted">{{ $account->company_name }}</p>
</div>

<div class="panel panel-body">
    @if($invoices->isEmpty())
        <p class="muted">Noch keine Monatsrechnungen vorhanden.</p>
    @else
        <table style="width:100%;">
            <thead>
            <tr>
                <th>Rechnung</th>
                <th>Monat</th>
                <th>Tickets</th>
                <th>Generiert am</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->invoice_label }}</td>
                    <td>{{ $invoice->tickets->count() }}</td>
                    <td>{{ $invoice->generated_at?->format('d.m.Y H:i') }}</td>
					<td><a href="{{ route('cibena-portal.monthly-invoices.pdf', $invoice) }}" class="btn btn-sm">PDF</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:1rem;">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection