@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Kundenanfragen</h1>
            <p class="muted">Interne Pruefung und Verknuepfung von Kundenportal-Anfragen.</p>
        </div>
    </div>

    <form class="panel panel-body filters" method="get">
        <div>
            <label for="q">Suche</label>
            <input id="q" name="q" value="{{ $search }}" placeholder="Firma, E-Mail, PLZ, Kundennummer">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Alle</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn" type="submit">Filtern</button>
        <a class="btn secondary" href="{{ route('customer-portal-requests.index') }}">Zuruecksetzen</a>
    </form>

    <div class="panel panel-body">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Eingang</th>
                    <th>Firma</th>
                    <th>Kontakt</th>
                    <th>E-Mail</th>
                    <th>PLZ / Ort</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $portalRequest)
                    <tr>
                        <td>{{ $portalRequest->created_at?->format('d.m.Y H:i') }}</td>
                        <td>{{ $portalRequest->company_name }}</td>
                        <td>{{ $portalRequest->contact_name }}</td>
                        <td>{{ $portalRequest->email }}</td>
                        <td>{{ trim(($portalRequest->zip ?? '').' '.($portalRequest->city ?? '')) }}</td>
                        <td><span class="badge">{{ $portalRequest->statusLabel() }}</span></td>
                        <td><a class="btn secondary" href="{{ route('customer-portal-requests.show', $portalRequest) }}">Pruefen</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">Keine Kundenanfragen gefunden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $requests->links() }}</div>
    </div>
@endsection
