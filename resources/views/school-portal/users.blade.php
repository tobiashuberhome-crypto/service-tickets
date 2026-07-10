@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <a class="muted" href="{{ route('school-portal.dashboard') }}">&larr; Übersicht</a>
            <h1>Benutzerverwaltung</h1>
            <p class="muted">Konten für Ihre Schule verwalten</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width:680px; margin-bottom:1rem;">
        <h3 style="margin:0;">Neuen Benutzer anlegen</h3>
        <form method="post" action="{{ route('school-portal.users.store') }}" class="stack">
            @csrf
            <div class="grid grid-2" style="gap:.75rem;">
                <div>
                    <label for="contact_name">Name *</label>
                    <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required>
                    @error('contact_name')<p class="text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email">E-Mail *</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')<p class="text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone">Telefon</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                </div>
                <div>
                    <label for="initial_password">Initiales Passwort *</label>
                    <input id="initial_password" name="initial_password" type="password" minlength="8" required>
                    @error('initial_password')<p class="text-danger">{{ $message }}</p>@enderror
                </div>
            </div>
            <label style="display:flex; align-items:center; gap:.5rem;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked>
                Konto sofort aktiv
            </label>
            <button class="btn" type="submit">Benutzer anlegen</button>
        </form>
    </div>

    <h2>Bestehende Benutzer</h2>
    @forelse ($users as $user)
        <form method="post" action="{{ route('school-portal.users.update', $user) }}" class="panel panel-body stack" style="margin-bottom:.75rem;">
            @csrf
            @method('PUT')

            <div class="grid grid-2" style="gap:.75rem;">
                <div>
                    <label>Name</label>
                    <input name="contact_name" type="text" value="{{ old('contact_name', $user->contact_name) }}" required>
                </div>
                <div>
                    <label>E-Mail</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div>
                    <label>Telefon</label>
                    <input name="phone" type="text" value="{{ old('phone', $user->phone) }}">
                </div>
                <div>
                    <label>Neues Passwort (optional)</label>
                    <input name="new_password" type="password" minlength="8" placeholder="nur bei Reset setzen">
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;">
                <label style="display:flex; align-items:center; gap:.5rem;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}>
                    aktiv
                </label>
                <div class="muted" style="font-size:.85rem;">
                    Letzter Login: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '–' }}
                </div>
                <button class="btn secondary" type="submit">Speichern</button>
            </div>
        </form>
    @empty
        <div class="panel panel-body">
            <p class="muted">Noch keine Benutzer vorhanden.</p>
        </div>
    @endforelse
@endsection
