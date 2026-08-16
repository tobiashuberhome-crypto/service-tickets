@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Interne Tickets</h1>
</div>

{{-- Filter --}}
<form method="get" class="filter-bar" style="margin-bottom:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
    <select name="quelle" class="form-control" style="width:auto;">
        <option value="">Alle Quellen</option>
        <option value="lager"        @selected(request('quelle') === 'lager')>Lager</option>
        <option value="zeitebuchung" @selected(request('quelle') === 'zeitebuchung')>Zeitebuchung</option>
    </select>
    <select name="typ" class="form-control" style="width:auto;">
        <option value="">Alle Typen</option>
        <option value="bug"      @selected(request('typ') === 'bug')>Bug</option>
        <option value="feature"  @selected(request('typ') === 'feature')>Feature</option>
        <option value="aufgabe"  @selected(request('typ') === 'aufgabe')>Aufgabe</option>
    </select>
    <select name="status" class="form-control" style="width:auto;">
        <option value="">Alle Status</option>
        <option value="offen"          @selected(request('status') === 'offen')>Offen</option>
        <option value="in_bearbeitung" @selected(request('status') === 'in_bearbeitung')>In Bearbeitung</option>
        <option value="erledigt"       @selected(request('status') === 'erledigt')>Erledigt</option>
    </select>
    <button type="submit" class="btn">Filtern</button>
    @if(request()->hasAny(['quelle','typ','status']))
        <a href="{{ route('interne-tickets.index') }}" class="btn secondary">Zurücksetzen</a>
    @endif
</form>

<div class="card" style="overflow-x:auto;">
    <table class="data-table" style="width:100%;">
        <thead>
            <tr>
                <th>Nr.</th>
                <th>Quelle</th>
                <th>Typ</th>
                <th>Titel</th>
                <th>Priorität</th>
                <th>Ersteller</th>
                <th>Erstellt</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td><code>{{ $ticket->ticket_number }}</code></td>
                <td>{{ $ticket->quelleLabel() }}</td>
                <td style="text-transform:capitalize;">{{ $ticket->typ }}</td>
                <td>
                    <strong>{{ $ticket->titel }}</strong>
                    @if($ticket->beschreibung)
                        <div style="font-size:.85em; color:#666; margin-top:.2rem;">{{ Str::limit($ticket->beschreibung, 120) }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $ticket->prioritaetBadgeClass() }}" style="text-transform:capitalize;">
                        {{ $ticket->prioritaet }}
                    </span>
                </td>
                <td style="font-size:.85em;">
                    {{ $ticket->ersteller_name }}<br>
                    <span style="color:#888;">{{ $ticket->ersteller_email }}</span>
                </td>
                <td style="white-space:nowrap; font-size:.85em;">
                    {{ $ticket->created_at->format('d.m.Y H:i') }}
                </td>
                <td>
                    <form method="post" action="{{ route('interne-tickets.status', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-control" style="font-size:.85em;" onchange="this.form.submit()">
                            <option value="offen"          @selected($ticket->status === 'offen')>Offen</option>
                            <option value="in_bearbeitung" @selected($ticket->status === 'in_bearbeitung')>In Bearbeitung</option>
                            <option value="erledigt"       @selected($ticket->status === 'erledigt')>Erledigt</option>
                        </select>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; color:#888; padding:2rem;">
                    Keine internen Tickets gefunden.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1rem;">
    {{ $tickets->links() }}
</div>
@endsection
