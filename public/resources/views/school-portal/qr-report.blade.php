@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Maschinenmeldung</h1>
            <p class="muted">{{ $room->name }} · {{ $machine->machine_ref_snapshot }}{{ $machine->serial_number ? ' · SN '.$machine->serial_number : '' }}</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width:640px;">
        <p class="muted" style="margin-top:0;">
            Bitte wählen Sie den Grund aus. Die Meldung wird an CIBENA und die Schulverwaltung übermittelt.
        </p>

        <form method="post" action="{{ route('school-portal.qr.submit', ['token' => $token]) }}" class="stack">
            @csrf

            <div>
                <label for="problem_type">Problemgrund *</label>
                <select id="problem_type" name="problem_type" required>
                    <option value="">– bitte wählen –</option>
                    @foreach (['Maschine näht nicht','Faden reißt','Nadel bricht','Stichbild unregelmäßig','Maschine macht Geräusche','Pedal / Kabel defekt','Wartung gewünscht','Sonstiges'] as $problem)
                        <option value="{{ $problem }}" {{ old('problem_type') === $problem ? 'selected' : '' }}>{{ $problem }}</option>
                    @endforeach
                </select>
                @error('problem_type')<p class="text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description">Kurzbeschreibung <span class="muted">(bei "Sonstiges" Pflicht)</span></label>
                <textarea id="description" name="description" rows="4" placeholder="Was passiert genau?">{{ old('description') }}</textarea>
                @error('description')<p class="text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-2" style="gap:.75rem;">
                <div>
                    <label for="contact_name">Ihr Name *</label>
                    <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required>
                    @error('contact_name')<p class="text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact_email">E-Mail (optional)</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email') }}">
                    @error('contact_email')<p class="text-danger">{{ $message }}</p>@enderror
                </div>
            </div>

            <button class="btn" type="submit">Meldung senden</button>
        </form>
    </div>
@endsection