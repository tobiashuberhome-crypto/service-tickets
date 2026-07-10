@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <a class="muted" href="{{ route('school-portal.rooms.show', $room) }}">&larr; {{ $room->name }}</a>
            <h1>{{ $machine->machine_ref_snapshot }}</h1>
            @if ($machine->manufacturer_snapshot)
                <p class="muted">{{ $machine->manufacturer_snapshot }}{{ $machine->serial_number ? ' · SN ' . $machine->serial_number : '' }}</p>
            @endif
        </div>
        <a class="btn" href="{{ route('school-portal.tickets.create', $machine) }}" style="background:#dc2626; color:#fff;">Problem melden</a>
    </div>

    <div class="panel panel-body" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">QR-Code für Meldungen</h2>
        <p class="muted" style="margin-top:0;">Dieser QR-Code öffnet die öffentliche Meldeseite für genau diese Maschine.</p>

        <div style="display:flex; gap:1rem; align-items:flex-start; flex-wrap:wrap;">
            <img
                src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrPublicUrl) }}"
                alt="QR-Code für Maschine {{ $machine->machine_ref_snapshot }}"
                style="border:1px solid #e2e8f0; border-radius:8px; background:#fff; padding:.5rem;"
            >

            <div style="max-width:520px;">
                <label style="display:block; font-weight:600; margin-bottom:.25rem;">Meldelink</label>
                <input type="text" readonly value="{{ $qrPublicUrl }}" onclick="this.select()" style="width:100%; margin-bottom:.5rem;">

                <form method="post" action="{{ route('school-portal.machines.qr-regenerate', $machine) }}" style="display:inline-block; margin-right:.5rem;">
                    @csrf
                    <button class="btn secondary" type="submit">QR-Code neu erzeugen</button>
                </form>

                <a class="btn" href="{{ $qrPublicUrl }}" target="_blank" rel="noopener">Meldeseite öffnen</a>
            </div>
        </div>
    </div>

    <h2>Servicehistorie</h2>
    @forelse ($tickets as $ticket)
        <div class="panel panel-body" style="margin-bottom:.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:.5rem;">
                <div>
                    <strong>{{ $ticket->ticket_number }}</strong>
                    <span class="muted" style="margin-left:.5rem;">{{ $ticket->acceptance_date?->format('d.m.Y') }}</span>
                </div>
                <span class="badge {{ in_array($ticket->status, ['done','delivered']) ? 'badge-ok' : 'badge-ticket' }}">
                    {{ $ticket->statusLabel() }}
                </span>
            </div>
            @if ($ticket->error_description)
                <p class="muted" style="margin:.5rem 0 0; white-space:pre-line;">{{ \Illuminate\Support\Str::limit($ticket->error_description, 150) }}</p>
            @endif
        </div>
    @empty
        <div class="panel panel-body">
            <p class="muted">Noch keine Serviceeinträge für diese Maschine.</p>
        </div>
    @endforelse
@endsection