@extends('layouts.customer-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Neues Ticket erstellen</h1>
            <p class="muted">Kunde: {{ $account->company_name }}</p>
        </div>
        <a class="btn secondary" href="{{ route('customer-portal.dashboard') }}">Zurueck</a>
    </div>

    <form method="post" action="{{ route('customer-portal.tickets.store') }}" class="panel panel-body stack" style="max-width: 860px">
        @csrf
        <div class="section">
            <div class="section-title"><h2>Maschine</h2></div>
            <div class="form-row">
                <div>
                    <label for="manufacturer_snapshot">Hersteller</label>
                    <input id="manufacturer_snapshot" name="manufacturer_snapshot" value="{{ old('manufacturer_snapshot') }}">
                </div>
                <div>
                    <label for="machine_ref_snapshot">Maschinen-Typ / Modell *</label>
                    <input id="machine_ref_snapshot" name="machine_ref_snapshot" value="{{ old('machine_ref_snapshot') }}" required>
                </div>
            </div>
            <div>
                <label for="serial_number">Seriennummer</label>
                <input id="serial_number" name="serial_number" value="{{ old('serial_number') }}">
            </div>
        </div>

        <div class="section">
            <div class="section-title"><h2>Anliegen</h2></div>
            <label class="check-row">
                <input type="checkbox" name="service_enabled" value="1" @checked(old('service_enabled'))>
                Service
            </label>
            <label class="check-row">
                <input type="checkbox" name="cleaning" value="1" @checked(old('cleaning'))>
                Reinigung
            </label>
            <label class="check-row">
                <input type="checkbox" name="repair_enabled" value="1" @checked(old('repair_enabled'))>
                Reparatur
            </label>
    <!--        <label class="check-row">
                <input type="checkbox" name="spare_part_order_required" value="1" @checked(old('spare_part_order_required'))>
                Ersatzteilbestellung erforderlich / erwartet
            </label> -->
            <div>
                <label for="error_description">Beschreibung *</label>
                <textarea id="error_description" name="error_description" required>{{ old('error_description') }}</textarea>
            </div>
        </div>

        <div class="button-row">
            <button class="btn" type="submit">Ticket senden</button>
            <a class="btn secondary" href="{{ route('customer-portal.dashboard') }}">Abbrechen</a>
        </div>
    </form>
@endsection
