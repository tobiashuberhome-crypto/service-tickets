@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <a class="muted" href="{{ route('school-portal.dashboard') }}">&larr; Übersicht</a>
            <h1>{{ $room->name }}</h1>
            <p class="muted">{{ $machines->count() }} {{ $machines->count() === 1 ? 'Maschine' : 'Maschinen' }}</p>
        </div>
        <button class="btn secondary" onclick="document.getElementById('add-machine-form').style.display='block'; this.style.display='none'">
            + Maschine erfassen
        </button>
    </div>

    {{-- Maschine hinzufügen --}}
    <form id="add-machine-form" method="post" action="{{ route('school-portal.machines.store', $room) }}"
          class="panel panel-body stack" style="display:none; max-width:520px; margin-bottom:1.5rem;">
        @csrf
        <h3 style="margin:0">Neue Maschine erfassen</h3>
        <div class="grid grid-2" style="gap:.75rem;">
            <div>
                <label for="m-ref">Modellbezeichnung *</label>
                <input id="m-ref" name="machine_ref_snapshot" type="text" placeholder="z.B. Pfaff Select 3.2" required value="{{ old('machine_ref_snapshot') }}">
                @error('machine_ref_snapshot')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="m-mfr">Hersteller</label>
                <input id="m-mfr" name="manufacturer_snapshot" type="text" placeholder="z.B. Pfaff" value="{{ old('manufacturer_snapshot') }}">
            </div>
            <div>
                <label for="m-sn">Seriennummer</label>
                <input id="m-sn" name="serial_number" type="text" placeholder="z.B. SN-12345" value="{{ old('serial_number') }}">
            </div>
            <div>
                <label for="m-inv">Inventar-Nr. der Schule</label>
                <input id="m-inv" name="inventory_number" type="text" placeholder="z.B. 2024-017" value="{{ old('inventory_number') }}">
            </div>
        </div>
        <div style="display:flex; gap:.5rem;">
            <button class="btn" type="submit">Maschine speichern</button>
            <button class="btn secondary" type="button"
                    onclick="this.closest('form').style.display='none'; document.querySelector('[onclick*=add-machine]').style.display=''">
                Abbrechen
            </button>
        </div>
    </form>

    @if ($machines->isEmpty())
        <div class="panel panel-body">
            <p class="muted">Noch keine Maschinen in diesem Raum erfasst. Klicken Sie oben auf „Maschine erfassen".</p>
        </div>
    @else
        <div class="machine-grid">
            @foreach ($machines as $machine)
                <div class="machine-card">
                    <h3>{{ $machine->machine_ref_snapshot }}</h3>
                    @if ($machine->manufacturer_snapshot)
                        <div class="machine-serial">{{ $machine->manufacturer_snapshot }}</div>
                    @endif
                    @if ($machine->serial_number)
                        <div class="machine-serial">SN: {{ $machine->serial_number }}</div>
                    @endif

                    <span class="badge {{ $machine->status_class === 'status-ok' ? 'badge-ok' : 'badge-ticket' }}" style="margin-bottom:.75rem; display:block; width:fit-content;">
                        {{ $machine->status_label }}
                    </span>

                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <a class="btn" href="{{ route('school-portal.machines.show', $machine) }}">Details</a>
                        <a class="btn secondary" href="{{ route('school-portal.tickets.create', $machine) }}">Problem melden</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
