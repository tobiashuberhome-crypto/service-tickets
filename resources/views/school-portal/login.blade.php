@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <p class="login-logo"><img src="{{ asset('img/logo-cibena.png') }}" alt="Cibena Logo"></p>
            <h1>Login - Schul-Serviceportal</h1>
            <p class="muted">Anmeldung mit E-Mail und Passwort, optional auch per Magic Link.</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width:560px; margin-bottom:1rem;">
        <h3>Login mit E-Mail und Passwort</h3>
        <form method="post" action="{{ route('school-portal.password.login') }}" class="stack">
            @csrf
            <div>
                <label for="password-email">E-Mail-Adresse</label>
                <input id="password-email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password">Passwort</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="btn" type="submit">Mit Passwort anmelden</button>
        </form>
    </div>

    <div class="panel panel-body stack" style="max-width:560px;">
        <h3>Alternativ: Magic Link</h3>
        <p class="muted">Sie erhalten einen Einmal-Link an Ihre E-Mail-Adresse, der 30 Minuten gueltig ist.</p>
        <form method="post" action="{{ route('school-portal.magic.send') }}" class="stack">
            @csrf
            <div>
                <label for="magic-email">E-Mail-Adresse</label>
                <input id="magic-email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <button class="btn secondary" type="submit">Magic Link senden</button>
        </form>
    </div>
@endsection