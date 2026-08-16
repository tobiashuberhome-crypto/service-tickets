# Datei: resources\views\customer-portal-geiser\login.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\customer-portal-geiser\login.blade.php`
- **Stand:** 2026-06-30 19:32:01
- **Typ:** blade

## Code

```blade
@extends('layouts.customer-portal-geiser')

@section('content')
    <div class="page-header">
        <div>
            <h1>Login fuer Geiser-Serviceportal</h1>
            <p class="muted">Sie koennen sich entweder per Magic Link oder direkt mit E-Mail und Passwort anmelden.</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width: 640px; margin-bottom: 1rem;">
        <h3>Login mit E-Mail und Passwort</h3>
        <form method="post" action="{{ route('geiser-portal.password.login') }}" class="stack">
            @csrf
            <div>
                <label for="password-email">E-Mail</label>
                <input id="password-email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label for="password">Passwort</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="btn" type="submit">Mit Passwort anmelden</button>
        </form>
        <p class="muted">Passwort vergessen oder noch keines vergeben?</p>
        <form method="post" action="{{ route('geiser-portal.password.reset.send') }}" class="stack">
            @csrf
            <div>
                <label for="reset-email">E-Mail fuer Passwort-Link</label>
                <input id="reset-email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <button class="btn secondary" type="submit">Link zur Passwortvergabe senden</button>
        </form>
    </div>

    <div class="panel panel-body stack" style="max-width: 640px">
        <h3>Alternativ: Magic Link</h3>
        <form method="post" action="{{ route('geiser-portal.magic.send') }}" class="stack">
            @csrf
            <div>
                <label for="magic-email">E-Mail</label>
                <input id="magic-email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <button class="btn" type="submit">Magic Link senden</button>
        </form>
    </div>
@endsection

```
