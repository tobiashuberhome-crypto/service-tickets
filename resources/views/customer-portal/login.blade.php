@extends('layouts.customer-portal')

@section('content')
    <div class="page-header">
        <div>
            <p class="login-logo"><img src="{{ asset('img/logo-cibena.png') }}" alt="Cibena Logo"></p>
            <h1>Login Kundenportal</h1>
            <p class="muted">Sie koennen sich mit E-Mail und Passwort anmelden oder alternativ einen Magic Link anfordern.</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width: 640px; margin-bottom: 1rem;">
        <h3>Login mit E-Mail und Passwort</h3>
        <form method="post" action="{{ route('customer-portal.password.login') }}" class="stack">
            @csrf
            <div>
                <label for="password-email">E-Mail</label>
                <input id="password-email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password">Passwort</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="btn" type="submit">Anmelden</button>
        </form>
    </div>

    <div class="panel panel-body stack" style="max-width: 640px">
        <h3>Alternativ: Magic Link</h3>
        <form method="post" action="{{ route('customer-portal.magic.send') }}" class="stack">
            @csrf
            <div>
                <label for="magic-email">E-Mail</label>
                <input id="magic-email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <button class="btn secondary" type="submit">Magic Link senden</button>
        </form>
    </div>
@endsection
