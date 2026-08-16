@extends('layouts.customer-portal-cibena')

@section('content')
    <div class="page-header">
        <div>
            <h1>Neues Passwort vergeben</h1>
            <p class="muted">Bitte vergeben Sie ein neues Passwort fuer Ihr Cibena-Serviceportal-Konto.</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width: 640px;">
        <form method="post" action="{{ route('cibena-portal.password.reset', ['token' => $token]) }}" class="stack">
            @csrf
            <div>
                <label>E-Mail</label>
                <input type="email" value="{{ $email }}" readonly>
            </div>
            <div>
                <label for="password">Neues Passwort</label>
                <input id="password" name="password" type="password" minlength="8" required autofocus>
            </div>
            <div>
                <label for="password_confirmation">Passwort wiederholen</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
            </div>
            <button class="btn" type="submit">Passwort speichern</button>
        </form>
    </div>
@endsection
