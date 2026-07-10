@extends('layouts.customer-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Magic Link anfordern</h1>
            <p class="muted">Geben Sie Ihre freigeschaltete E-Mail-Adresse ein. Falls ein Zugang existiert, senden wir einen Login-Link.</p>
        </div>
    </div>

    <div class="panel panel-body stack" style="max-width: 640px">
        <form method="post" action="{{ route('customer-portal.magic.send') }}" class="stack">
            @csrf
            <div>
                <label for="email">E-Mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button class="btn" type="submit">Magic Link senden</button>
        </form>
    </div>
@endsection
