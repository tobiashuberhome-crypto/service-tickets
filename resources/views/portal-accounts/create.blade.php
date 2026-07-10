@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <a class="muted" href="{{ route('portal-accounts.index') }}">&larr; Portal-Konten</a>
            <h1>Neues Portal-Konto anlegen</h1>
        </div>
    </div>

    <form method="post" action="{{ route('portal-accounts.store') }}" class="stack" style="max-width:640px;">
        @csrf
        @include('portal-accounts._form', ['account' => $account, 'scopes' => $scopes, 'isCreate' => true])
        <div style="display:flex; gap:.5rem;">
            <button class="btn" type="submit">Konto anlegen</button>
            <a class="btn secondary" href="{{ route('portal-accounts.index') }}">Abbrechen</a>
        </div>
    </form>
@endsection
