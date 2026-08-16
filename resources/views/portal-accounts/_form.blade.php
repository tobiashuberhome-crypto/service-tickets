{{-- Gemeinsames Formular-Partial für Create & Edit --}}

<div class="panel panel-body stack">
    <h3 style="margin:0">Portal & Zugangsdaten</h3>

    <div>
        <label for="portal_scope">Portal *</label>
        <select id="portal_scope" name="portal_scope" required>
            @foreach ($scopes as $key => $label)
                <option value="{{ $key }}" {{ old('portal_scope', $account->portal_scope) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('portal_scope')<p class="text-danger">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-2" style="gap:.75rem;">
        <div>
            <label for="company_name">Firma / Schulname *</label>
            <input id="company_name" name="company_name" type="text"
                   value="{{ old('company_name', $account->company_name) }}" required>
            @error('company_name')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_name">Ansprechpartner</label>
            <input id="contact_name" name="contact_name" type="text"
                   value="{{ old('contact_name', $account->contact_name) }}">
        </div>
        <div>
            <label for="email">E-Mail-Adresse *</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email', $account->email) }}" required>
            @error('email')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="phone">Telefon</label>
            <input id="phone" name="phone" type="text"
                   value="{{ old('phone', $account->phone) }}">
        </div>
    </div>
</div>

<div class="panel panel-body stack">
    <h3 style="margin:0">Dolibarr-Verknüpfung</h3>
    <div class="grid grid-2" style="gap:.75rem;">
        <div>
            <label for="dolibarr_thirdparty_id">Dolibarr Kunden-ID *</label>
            <input id="dolibarr_thirdparty_id" name="dolibarr_thirdparty_id" type="number"
                   value="{{ old('dolibarr_thirdparty_id', $account->dolibarr_thirdparty_id) }}" required min="1">
            @error('dolibarr_thirdparty_id')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="dolibarr_customer_code">Kundennummer</label>
            <input id="dolibarr_customer_code" name="dolibarr_customer_code" type="text"
                   value="{{ old('dolibarr_customer_code', $account->dolibarr_customer_code) }}">
        </div>
    </div>
</div>

<div class="panel panel-body stack">
    <h3 style="margin:0">Passwort</h3>
    @if ($isCreate)
        <p class="muted">Initiales Passwort setzen (min. 8 Zeichen). Der Benutzer kann es später über den Passwort-Reset ändern.</p>
    @else
        <p class="muted">Nur ausfüllen, wenn das Passwort zurückgesetzt werden soll. Leer lassen = unverändert.</p>
    @endif
    <div>
        <label for="initial_password">{{ $isCreate ? 'Initiales Passwort' : 'Neues Passwort' }}</label>
        <input id="initial_password" name="initial_password" type="password"
               autocomplete="new-password" placeholder="min. 8 Zeichen"
               {{ $isCreate ? '' : '' }}>
        @error('initial_password')<p class="text-danger">{{ $message }}</p>@enderror
    </div>
</div>

<div class="panel panel-body stack">
    <h3 style="margin:0">Status</h3>
    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1"
               {{ old('is_active', $account->exists ? ($account->is_active ? '1' : '0') : '1') === '1' ? 'checked' : '' }}>
        Konto aktiv (Benutzer kann sich einloggen)
    </label>
</div>
