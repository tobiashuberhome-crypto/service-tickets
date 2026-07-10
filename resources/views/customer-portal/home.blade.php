@extends('layouts.customer-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Kundenportal</h1>
            <p class="muted">Senden Sie eine Anfrage zur Freischaltung oder melden Sie sich mit einem Magic Link an.</p>
        </div>
        <a class="btn secondary" href="{{ route('customer-portal.login') }}">Magic Link anfordern</a>
    </div>

    <div class="grid grid-2">
        <section class="panel panel-body stack">
            <div>
                <h2>Zugang anfragen / identifizieren</h2>
                <p class="muted">Ihre Angaben werden intern geprueft und mit den Kundendaten abgeglichen. Nach Freigabe koennen Sie Tickets erstellen.</p>
            </div>

            <form method="post" action="{{ route('customer-portal.requests.store') }}" class="stack">
                @csrf
                <div>
                    <label for="company_name">Firma / Name *</label>
                    <input id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                </div>
                <div class="form-row">
                    <div>
                        <label for="contact_name">Ansprechpartner</label>
                        <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}">
                    </div>
                    <div>
                        <label for="email">E-Mail *</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label for="phone">Telefon</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label for="customer_number_input">Kundennummer, falls bekannt</label>
                        <input id="customer_number_input" name="customer_number_input" value="{{ old('customer_number_input') }}">
                    </div>
                </div>
                <div>
                    <label for="street">Strasse</label>
                    <input id="street" name="street" value="{{ old('street') }}">
                </div>
                <div class="form-row">
                    <div>
                        <label for="zip">PLZ</label>
                        <input id="zip" name="zip" value="{{ old('zip') }}">
                    </div>
                    <div>
                        <label for="city">Ort</label>
                        <input id="city" name="city" value="{{ old('city') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label for="machine_serial">Maschinen-Seriennummer</label>
                        <input id="machine_serial" name="machine_serial" value="{{ old('machine_serial') }}">
                    </div>
                    <div>
                        <label for="invoice_or_order_number">Rechnungs-/Auftragsnummer</label>
                        <input id="invoice_or_order_number" name="invoice_or_order_number" value="{{ old('invoice_or_order_number') }}">
                    </div>
                </div>
                <div>
                    <label for="message">Nachricht</label>
                    <textarea id="message" name="message">{{ old('message') }}</textarea>
                </div>
                <button class="btn" type="submit">Anfrage senden</button>
            </form>
        </section>

        <aside class="panel panel-body stack">
            <h2>Bereits freigeschaltet?</h2>
            <p class="muted">Fordern Sie mit Ihrer E-Mail-Adresse einen einmaligen Magic Link an. Der Link ist 30 Minuten gueltig und kann nur einmal verwendet werden.</p>
            <a class="btn" href="{{ route('customer-portal.login') }}">Magic Link anfordern</a>
        </aside>
    </div>
@endsection
