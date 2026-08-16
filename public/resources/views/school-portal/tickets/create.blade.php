@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <a class="muted" href="{{ route('school-portal.machines.show', $machine) }}">&larr; {{ $machine->machine_ref_snapshot }}</a>
            <h1>Problem melden</h1>
            <p class="muted">{{ $room->name }} · {{ $machine->manufacturer_snapshot }} {{ $machine->serial_number ? '· SN '.$machine->serial_number : '' }}</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width:600px">
        <form method="post" action="{{ route('school-portal.tickets.store', $machine) }}" class="stack">
            @csrf

            <div>
                <label for="problem_type">Was ist das Problem? *</label>
                <select id="problem_type" name="problem_type" required>
                    <option value="">– bitte wählen –</option>
                    <option value="Maschine näht nicht" {{ old('problem_type') === 'Maschine näht nicht' ? 'selected' : '' }}>Maschine näht nicht</option>
                    <option value="Faden reißt" {{ old('problem_type') === 'Faden reißt' ? 'selected' : '' }}>Faden reißt</option>
                    <option value="Nadel bricht" {{ old('problem_type') === 'Nadel bricht' ? 'selected' : '' }}>Nadel bricht</option>
                    <option value="Stichbild unregelmäßig" {{ old('problem_type') === 'Stichbild unregelmäßig' ? 'selected' : '' }}>Stichbild unregelmäßig</option>
                    <option value="Maschine macht Geräusche" {{ old('problem_type') === 'Maschine macht Geräusche' ? 'selected' : '' }}>Maschine macht Geräusche</option>
                    <option value="Pedal / Kabel defekt" {{ old('problem_type') === 'Pedal / Kabel defekt' ? 'selected' : '' }}>Pedal / Kabel defekt</option>
                    <option value="Wartung gewünscht" {{ old('problem_type') === 'Wartung gewünscht' ? 'selected' : '' }}>Wartung gewünscht</option>
                    <option value="Sonstiges" {{ old('problem_type') === 'Sonstiges' ? 'selected' : '' }}>Sonstiges</option>
                </select>
                @error('problem_type')<p class="text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="priority">Dringlichkeit *</label>
                <select id="priority" name="priority" required>
                    <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>normal</option>
                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>niedrig</option>
                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>dringend</option>
                </select>
                @error('priority')<p class="text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description">Kurzbeschreibung (optional)</label>
                <textarea id="description" name="description" rows="4" placeholder="Weitere Details zum Problem …">{{ old('description') }}</textarea>
                @error('description')<p class="text-danger">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex; gap:.5rem;">
                <button class="btn" type="submit">Ticket absenden</button>
                <a class="btn secondary" href="{{ route('school-portal.machines.show', $machine) }}">Abbrechen</a>
            </div>
        </form>
    </div>

    <div class="panel panel-body" style="max-width:600px; margin-top:1rem; background:#f8fafc;">
        <p class="muted" style="margin:0;">
            Nach dem Absenden erhalten Sie eine Bestätigung und wir melden uns zur weiteren Abstimmung.
        </p>
    </div>
@endsection
