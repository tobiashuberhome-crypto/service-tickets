@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Willkommen, {{ $account->company_name }}</h1>
            <p class="muted">Uebersicht Ihrer Raeume und Maschinen</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $totalMachines }}</div>
            <div class="stat-label">Naehmaschinen gesamt</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $machinesWithOpenTickets }}</div>
            <div class="stat-label">Maschinen mit offenem Ticket</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $openTickets }}</div>
            <div class="stat-label">Offene Tickets</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $rooms->count() }}</div>
            <div class="stat-label">Raeume</div>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem;">
        <h2 style="margin:0">Ihre Raeume</h2>
        <button id="show-add-room" class="btn secondary" type="button">+ Raum hinzufuegen</button>
    </div>

    <form id="add-room-form" method="post" action="{{ route('school-portal.rooms.store') }}" class="panel panel-body stack" style="display:none; max-width:400px; margin-bottom:1rem;">
        @csrf
        <label for="room-name">Raumname</label>
        <input id="room-name" name="name" type="text" placeholder="z. B. Textilraum 1" required>
        <div style="display:flex; gap:.5rem;">
            <button class="btn" type="submit">Anlegen</button>
            <button id="hide-add-room" class="btn secondary" type="button">Abbrechen</button>
        </div>
    </form>

    @if ($rooms->isEmpty())
        <div class="panel panel-body">
            <p class="muted">Noch keine Raeume angelegt. Fuegen Sie oben Ihren ersten Raum hinzu.</p>
        </div>
    @else
        <div class="room-grid">
            @foreach ($rooms as $room)
                @php
                    $machineCount = $room->machines->count();
                    $openCount = $room->machines->sum(fn ($machine) => $machine->tickets->count());
                @endphp
                <article class="room-card">
                    <div>
                        <h3>{{ $room->name }}</h3>
                        <p class="room-meta">{{ $machineCount }} {{ $machineCount === 1 ? 'Maschine' : 'Maschinen' }}</p>
                    </div>
                    <div>
                        @if ($openCount > 0)
                            <span class="badge badge-ticket">{{ $openCount }} Ticket offen</span>
                        @else
                            <span class="badge badge-ok">alles in Ordnung</span>
                        @endif
                    </div>
                    <a class="btn" href="{{ route('school-portal.rooms.show', $room) }}">Oeffnen</a>
                </article>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
<script>
(() => {
    const showButton = document.getElementById('show-add-room');
    const hideButton = document.getElementById('hide-add-room');
    const form = document.getElementById('add-room-form');

    if (!showButton || !hideButton || !form) return;

    showButton.addEventListener('click', () => {
        form.style.display = 'block';
        showButton.style.display = 'none';
    });

    hideButton.addEventListener('click', () => {
        form.style.display = 'none';
        showButton.style.display = '';
    });
})();
</script>
@endpush