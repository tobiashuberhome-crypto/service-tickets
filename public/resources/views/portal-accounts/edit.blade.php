@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <a class="muted" href="{{ route('portal-accounts.index') }}">&larr; Portal-Konten</a>
            <h1>Konto bearbeiten: {{ $account->company_name }}</h1>
        </div>
    </div>

    <form method="post" action="{{ route('portal-accounts.update', $account) }}" class="stack" style="max-width:640px;">
        @csrf
        @method('PUT')
        @include('portal-accounts._form', ['account' => $account, 'scopes' => $scopes, 'isCreate' => false])
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <button class="btn" type="submit">Änderungen speichern</button>
            <a class="btn secondary" href="{{ route('portal-accounts.index') }}">Abbrechen</a>
        </div>
    </form>

    <hr style="margin:2rem 0;">

    <form method="post" action="{{ route('portal-accounts.destroy', $account) }}"
          onsubmit="return confirm('Konto wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')"
          style="max-width:640px;">
        @csrf
        @method('DELETE')
        <button class="btn danger" type="submit">Konto löschen</button>
    </form>
@endsection
