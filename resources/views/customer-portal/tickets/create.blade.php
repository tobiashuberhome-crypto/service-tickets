@extends('layouts.customer-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Neues Ticket erstellen</h1>
            <p class="muted">Kunde: {{ $account->company_name }}</p>
        </div>
        <a class="btn secondary" href="{{ route('customer-portal.dashboard') }}">Zurueck</a>
    </div>

    <form method="post" action="{{ route('customer-portal.tickets.store') }}" class="panel panel-body stack" style="max-width: 860px" enctype="multipart/form-data">
        @csrf
        <div class="section">
            <div class="section-title"><h2>Ticket erstellen</h2></div>
            <div class="form-row">
                <div>
                    <label for="target_role">Rolle *</label>
                    <select id="target_role" name="target_role" required>
                        <option value="">Bitte waehlen</option>
                        <option value="superadmin" @selected(old('target_role') === 'superadmin')>Superadmin</option>
                        <option value="admin" @selected(old('target_role') === 'admin')>Admin</option>
                        <option value="member" @selected(old('target_role') === 'member')>Member</option>
                    </select>
                </div>
                <div>
                    <label for="type">Typ *</label>
                    <select id="type" name="type" required>
                        <option value="">Bitte waehlen</option>
                        <option value="feature" @selected(old('type') === 'feature')>Feature</option>
                        <option value="bug" @selected(old('type') === 'bug')>BUG</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="title">Titel *</label>
                <input id="title" name="title" value="{{ old('title') }}" required>
            </div>
            <div>
                <label for="description">Beschreibung *</label>
                <textarea id="description" name="description" required style="min-height: 180px; white-space: pre-wrap;">{{ old('description') }}</textarea>
            </div>
            <div>
                <label for="attachments">Anhänge</label>
                <input id="attachments" name="attachments[]" type="file" multiple>
            </div>
        </div>

        <div class="button-row">
            <button class="btn" type="submit">Ticket senden</button>
            <a class="btn secondary" href="{{ route('customer-portal.dashboard') }}">Abbrechen</a>
        </div>
    </form>
@endsection
