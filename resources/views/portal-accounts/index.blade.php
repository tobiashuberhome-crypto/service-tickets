@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Portal-Konten</h1>
            <p class="muted">Benutzer für alle drei Portale verwalten</p>
        </div>
        <a class="btn" href="{{ route('portal-accounts.create') }}">+ Neues Konto</a>
    </div>

    {{-- Filter --}}
    <form method="get" action="{{ route('portal-accounts.index') }}" class="panel panel-body" style="margin-bottom:1rem;">
        <div class="form-row">
            <div>
                <label for="q">Suche</label>
                <input id="q" name="q" type="text" value="{{ $search }}" placeholder="Name, E-Mail …">
            </div>
            <div>
                <label for="scope">Portal</label>
                <select id="scope" name="scope">
                    <option value="">Alle Portale</option>
                    @foreach ($scopes as $key => $label)
                        <option value="{{ $key }}" {{ $activeScope === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="button-row" style="align-items:end;">
                <button class="btn" type="submit">Filtern</button>
                <a class="btn secondary" href="{{ route('portal-accounts.index') }}">Zurücksetzen</a>
            </div>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Firma / Name</th>
                <th>E-Mail</th>
                <th>Portal</th>
                <th>Aktiv</th>
                <th>Letzter Login</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accounts as $account)
                <tr>
                    <td>
                        <strong>{{ $account->company_name }}</strong>
                        @if ($account->contact_name)
                            <br><span class="muted" style="font-size:.85rem;">{{ $account->contact_name }}</span>
                        @endif
                    </td>
                    <td>{{ $account->email }}</td>
                    <td>
                        <span class="badge {{ match($account->portal_scope) { 'school' => 'badge-ok', 'geiser' => 'badge-ticket', default => '' } }}"
                              style="{{ $account->portal_scope === 'default' ? 'background:#e0f2fe;color:#0369a1;' : '' }}">
                            {{ $scopes[$account->portal_scope] ?? $account->portal_scope }}
                        </span>
                    </td>
                    <td>
                        @if ($account->is_active)
                            <span style="color:#166534">✓ aktiv</span>
                        @else
                            <span style="color:#991b1b">✗ inaktiv</span>
                        @endif
                    </td>
                    <td class="muted" style="font-size:.85rem;">
                        {{ $account->last_login_at?->format('d.m.Y H:i') ?? '–' }}
                    </td>
                    <td>
                        <a class="btn secondary" href="{{ route('portal-accounts.edit', $account) }}">Bearbeiten</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Keine Konten gefunden.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
