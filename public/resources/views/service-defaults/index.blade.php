@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Serviceleistungen</h1>
            <p class="muted">Diese Dolibarr-Artikelnummern werden vorbereitet, wenn im Ticket Service aktiviert ist.</p>
        </div>
    </div>

    <form class="panel panel-body" method="post" action="{{ route('service-defaults.store') }}">
        @csrf
        <div class="grid grid-3">
            <div>
                <label for="product_ref">Dolibarr Artikelnummer</label>
                <input id="product_ref" name="product_ref" required>
            </div>
            <div>
                <label for="label">Bezeichnung</label>
                <input id="label" name="label">
            </div>
            <div>
                <label for="quantity">Menge</label>
                <input id="quantity" type="number" step="0.01" min="0.01" max="100" name="quantity" value="1.00" required>
            </div>
        </div>
        <div class="button-row" style="margin-top: 12px;">
            <label class="check-row">
                <input type="checkbox" name="active" value="1" checked>
                Aktiv
            </label>
            <button class="btn" type="submit">Hinzufuegen</button>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Ref</th>
                    <th>Bezeichnung</th>
                    <th>Menge</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($defaults as $default)
                    <tr>
                        <td>{{ $default->product_ref }}</td>
                        <td>{{ $default->label ?: '-' }}</td>
                        <td>{{ number_format((float) $default->quantity, 2, ',', '.') }}</td>
                        <td><span class="badge {{ $default->active ? 'done' : 'error' }}">{{ $default->active ? 'aktiv' : 'inaktiv' }}</span></td>
                        <td>
                            <form method="post" action="{{ route('service-defaults.destroy', $default) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Loeschen</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
