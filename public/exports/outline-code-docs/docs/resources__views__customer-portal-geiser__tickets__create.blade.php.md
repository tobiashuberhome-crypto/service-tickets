# Datei: resources\views\customer-portal-geiser\tickets\create.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\customer-portal-geiser\tickets\create.blade.php`
- **Stand:** 2026-06-27 20:43:14
- **Typ:** blade

## Code

```blade
@extends('layouts.customer-portal-geiser')

@section('content')
    <div class="page-header">
        <div>
            <h1>Neues Ticket erfassen</h1>
            <p class="muted">Der Auftrag wird immer fuer {{ $account->company_name }} in Dolibarr angelegt. Diese Stammdaten sind hier nicht aenderbar.</p>
        </div>
        <a class="btn secondary" href="{{ route('geiser-portal.dashboard') }}">Zurueck</a>
    </div>

    <form method="post" action="{{ route('geiser-portal.tickets.store') }}" class="panel panel-body stack" style="max-width: 980px" enctype="multipart/form-data" id="ticket-form">
        @csrf

        <div class="section">
            <div class="section-title"><h2>ðŸ“· Foto des Reparaturauftrags (optional)</h2></div>
            <p class="muted">Fotografieren Sie das ausgefuellte Formular. Das Bild wird komprimiert gespeichert und dem Ticket beigefuegt.</p>

            {{-- Dieses hidden input wird per JS mit dem komprimierten Bild befuellt und abgesendet --}}
            <input type="file" name="customer_photo" id="customer_photo_final" accept="image/*" style="display:none">

            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <button type="button" id="btn-camera" class="btn secondary" style="font-size:15px;">ðŸ“· Kamera oeffnen</button>
                <button type="button" id="btn-file"   class="btn secondary" style="font-size:15px;">ðŸ“ Datei auswaehlen</button>
                <span id="photo-filename" style="color:#555; font-size:13px;"></span>
            </div>

            {{-- Diese inputs werden nur zur Auswahl genutzt, aber nicht direkt abgesendet --}}
            <input type="file" id="input-camera" accept="image/*" capture="environment" style="display:none">
            <input type="file" id="input-file"   accept="image/*" style="display:none">

            <div id="photo-preview-wrap" style="display:none; margin-top:12px;">
                <img id="photo-preview" src="" alt="Vorschau"
                     style="max-width:100%; max-height:300px; border-radius:6px; border:1px solid #ddd;">
                <br>
                <button type="button" id="btn-remove-photo" class="btn secondary"
                        style="margin-top:8px; font-size:12px;">âœ• Foto entfernen</button>
            </div>
        </div>

        <div class="section">
            <div class="section-title"><h2>Fester Kunde aus Dolibarr</h2></div>
            <div class="grid grid-2">
                <div>
                    <label>Kunde</label>
                    <input value="{{ $account->company_name }}" readonly>
                </div>
                <div>
                    <label>Kundennummer / ID</label>
                    <input value="{{ $account->dolibarr_customer_code ?: 'ID '.$account->dolibarr_thirdparty_id }}" readonly>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title"><h2>Maschine</h2></div>
            <div class="grid grid-3">
                <div>
                    <label for="serial_number">Seriennummer *</label>
                    <input id="serial_number" name="serial_number" value="{{ old('serial_number') }}" required>
                </div>
                <div>
                    <label for="manufacturer_snapshot">Hersteller</label>
                    <input id="manufacturer_snapshot" name="manufacturer_snapshot" value="{{ old('manufacturer_snapshot') }}">
                </div>
                <div>
                    <label for="machine_ref_snapshot">Maschinen-Typ / Modell *</label>
                    <input id="machine_ref_snapshot" name="machine_ref_snapshot" value="{{ old('machine_ref_snapshot') }}" required>
                </div>
            </div>
            <p class="muted" id="serial_lookup_hint">Wenn die Seriennummer bereits erfasst wurde, werden die zugehoerigen Daten automatisch geladen.</p>
        </div>

        <div class="section">
            <div class="section-title"><h2>Kundendaten in der Ticket-DB</h2></div>
            <div class="grid grid-2">
                <div>
                    <label for="contact_name">Ansprechpartner *</label>
                    <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required>
                </div>
                <div>
                    <label for="email">E-Mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $account->email) }}">
                </div>
                <div>
                    <label for="phone">Telefon</label>
                    <input id="phone" name="phone" value="{{ old('phone', $account->phone) }}">
                </div>
                <div>
                    <label for="street">Strasse</label>
                    <input id="street" name="street" value="{{ old('street') }}">
                </div>
                <div>
                    <label for="zip">PLZ</label>
                    <input id="zip" name="zip" value="{{ old('zip') }}">
                </div>
                <div>
                    <label for="city">Ort</label>
                    <input id="city" name="city" value="{{ old('city') }}">
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title"><h2>Annahme / Zubehoer</h2></div>
            <label class="check-row">
                <input type="checkbox" name="warranty_claimed" value="1" @checked(old('warranty_claimed'))>
                Garantie
            </label>
            <div class="grid grid-3">
                <label class="check-row"><input type="checkbox" name="accessory_presser_foot" value="1" @checked(old('accessory_presser_foot'))> Naehfuss</label>
                <label class="check-row"><input type="checkbox" name="accessory_bobbin_case" value="1" @checked(old('accessory_bobbin_case'))> Spulenkapsel</label>
                <label class="check-row"><input type="checkbox" name="accessory_bobbin" value="1" @checked(old('accessory_bobbin'))> Unterfadenspule</label>
                <label class="check-row"><input type="checkbox" name="accessory_power_cable" value="1" @checked(old('accessory_power_cable'))> Kabel</label>
                <label class="check-row"><input type="checkbox" name="accessory_foot_pedal" value="1" @checked(old('accessory_foot_pedal'))> Fussanlasser</label>
                <label class="check-row"><input type="checkbox" name="accessory_case" value="1" @checked(old('accessory_case'))> Koffer</label>
            </div>
            <div>
                <label for="accessory_other">Sonstiges</label>
                <input id="accessory_other" name="accessory_other" value="{{ old('accessory_other') }}">
            </div>
        </div>

        <div class="section">
            <div class="section-title"><h2>Fehlerbild / Freigabe</h2></div>
            <div>
                <label for="error_description">Beschreibung *</label>
                <textarea id="error_description" name="error_description" required>{{ old('error_description') }}</textarea>
            </div>
            <div class="grid grid-2">
                <div>
                    <label for="repair_approval_limit">Reparatur ohne Ruecksprache bis EUR</label>
                    <input id="repair_approval_limit" name="repair_approval_limit" type="number" min="0" step="0.01" value="{{ old('repair_approval_limit') }}">
                </div>
                <div>
                    <label for="intake_note">Zusatzinfo</label>
                    <input id="intake_note" name="intake_note" value="{{ old('intake_note') }}">
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title"><h2>Voraussichtliche interne Positionen</h2></div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Artikel</th>
                        <th>Hinweis</th>
                        <th>Menge</th>
                        <th>Einzelpreis</th>
                        <th>Gesamt</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Arbeitseinheit Technik</td>
                        <td>10 Minuten pro Einheit</td>
                        <td><input type="number" min="0" step="1" value="{{ old('estimate_qty_tech', '9') }}" class="js-estimate-qty" data-price="16.90" name="estimate_qty_tech" style="width: 90px;"></td>
                        <td>16,90 EUR</td>
                        <td><strong class="js-estimate-row-total">0,00 EUR</strong></td>
                    </tr>
                    <tr>
                        <td>Servicegebuehr</td>
                        <td>Abwicklung Auftrag sowie Transport in die Werkstatt und zurueck</td>
                        <td><input type="number" min="0" step="1" value="{{ old('estimate_qty_service_fee', '1') }}" class="js-estimate-qty" data-price="29.00" name="estimate_qty_service_fee" style="width: 90px;"></td>
                        <td>29,00 EUR</td>
                        <td><strong class="js-estimate-row-total">0,00 EUR</strong></td>
                    </tr>
                    <tr>
                        <td>VDE-Pruefung</td>
                        <td>Schutzleiter- und Isolationspruefung</td>
                        <td><input type="number" min="0" step="1" value="{{ old('estimate_qty_vde', '1') }}" class="js-estimate-qty" data-price="6.50" name="estimate_qty_vde" style="width: 90px;"></td>
                        <td>6,50 EUR</td>
                        <td><strong class="js-estimate-row-total">0,00 EUR</strong></td>
                    </tr>
                    <tr>
                        <td>Verbrauchsmaterialien</td>
                        <td>Nadel, Faden sowie Fette und Oele</td>
                        <td><input type="number" min="0" step="1" value="{{ old('estimate_qty_consumables', '1') }}" class="js-estimate-qty" data-price="4.50" name="estimate_qty_consumables" style="width: 90px;"></td>
                        <td>4,50 EUR</td>
                        <td><strong class="js-estimate-row-total">0,00 EUR</strong></td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" style="text-align: right;">Geschaetzte Gesamtkosten</th>
                        <th><strong id="estimate_grand_total">0,00 EUR</strong></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <p class="muted">Diese Positionen sind nur zur Orientierung sichtbar. Die eigentlichen internen Leistungen werden erst in der Bearbeitung gepflegt und hier bewusst nicht eingeblendet.</p>
        </div>

        <div class="button-row">
            <button class="btn" type="submit">Ticket senden</button>
            <a class="btn secondary" href="{{ route('geiser-portal.dashboard') }}">Abbrechen</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
// --- Foto-Upload mit Kamera-Zugriff und Client-seitiger Kompression ---
(() => {
    const btnCamera   = document.getElementById('btn-camera');
    if (!btnCamera) return;

    const btnFile     = document.getElementById('btn-file');
    const btnRemove   = document.getElementById('btn-remove-photo');
    const inputCamera = document.getElementById('input-camera');
    const inputFile   = document.getElementById('input-file');
    const finalInput  = document.getElementById('customer_photo_final');
    const preview     = document.getElementById('photo-preview');
    const previewWrap = document.getElementById('photo-preview-wrap');
    const filename    = document.getElementById('photo-filename');

    btnCamera.addEventListener('click', () => inputCamera.click());
    btnFile.addEventListener('click',   () => inputFile.click());
    inputCamera.addEventListener('change', e => handleFile(e.target.files[0]));
    inputFile.addEventListener('change',   e => handleFile(e.target.files[0]));

    btnRemove.addEventListener('click', () => {
        preview.src = '';
        previewWrap.style.display = 'none';
        filename.textContent = '';
        finalInput.files = new DataTransfer().files;
        setRequiredFields(true); // Pflichtfelder wieder aktivieren
    });

    function handleFile(file) {
        if (!file) return;
        compressImage(file, 1200, 0.75, (blob, name) => {
            const dt = new DataTransfer();
            dt.items.add(new File([blob], name, { type: 'image/jpeg' }));
            finalInput.files = dt.files;
            preview.src = URL.createObjectURL(blob);
            previewWrap.style.display = 'block';
            filename.textContent = name + ' (' + Math.round(blob.size / 1024) + ' KB)';
            setRequiredFields(false); // Pflichtfelder aufheben, da Foto vorhanden
        });
    }

    // Pflichtfelder aktivieren/deaktivieren je nach Foto-Status
    function setRequiredFields(required) {
        const ids = ['serial_number', 'machine_ref_snapshot', 'contact_name', 'error_description'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (required) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }

    // Komprimiert via Canvas: max. 1200px, JPEG 75%
    function compressImage(file, maxDim, quality, callback) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                let w = img.width, h = img.height;
                if (w > maxDim || h > maxDim) {
                    if (w > h) { h = Math.round(h * maxDim / w); w = maxDim; }
                    else       { w = Math.round(w * maxDim / h); h = maxDim; }
                }
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(
                    blob => callback(blob, file.name.replace(/\.[^.]+$/, '') + '_compressed.jpg'),
                    'image/jpeg', quality
                );
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
})();
</script>

<script>
(() => {
    function formatEuro(value) {
        return `${Number(value).toFixed(2).replace('.', ',')} EUR`;
    }

    function initEstimateCalculator() {
        const qtyInputs = Array.from(document.querySelectorAll('.js-estimate-qty'));
        const rowTotals = Array.from(document.querySelectorAll('.js-estimate-row-total'));
        const grandTotal = document.getElementById('estimate_grand_total');

        if (!qtyInputs.length || !rowTotals.length || !grandTotal) {
            return;
        }

        const recalc = () => {
            let sum = 0;

            qtyInputs.forEach((input, index) => {
                const price = Number(input.dataset.price || '0');
                const qty = Number(input.value || '0');
                const row = Math.max(0, qty) * price;
                sum += row;
                if (rowTotals[index]) {
                    rowTotals[index].textContent = formatEuro(row);
                }
            });

            grandTotal.textContent = formatEuro(sum);
        };

        qtyInputs.forEach((input) => {
            input.addEventListener('input', recalc);
            input.addEventListener('change', recalc);
        });

        recalc();
    }

    initEstimateCalculator();

    const serialInput = document.getElementById('serial_number');
    const hint = document.getElementById('serial_lookup_hint');

    if (!serialInput || !hint) {
        return;
    }

    const fields = {
        manufacturer_snapshot: document.getElementById('manufacturer_snapshot'),
        machine_ref_snapshot: document.getElementById('machine_ref_snapshot'),
        contact_name: document.getElementById('contact_name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        street: document.getElementById('street'),
        zip: document.getElementById('zip'),
        city: document.getElementById('city'),
        accessory_other: document.getElementById('accessory_other'),
        repair_approval_limit: document.getElementById('repair_approval_limit'),
        intake_note: document.getElementById('intake_note'),
    };

    const checks = {
        warranty_claimed: document.querySelector('input[name="warranty_claimed"]'),
        accessory_presser_foot: document.querySelector('input[name="accessory_presser_foot"]'),
        accessory_bobbin_case: document.querySelector('input[name="accessory_bobbin_case"]'),
        accessory_bobbin: document.querySelector('input[name="accessory_bobbin"]'),
        accessory_power_cable: document.querySelector('input[name="accessory_power_cable"]'),
        accessory_foot_pedal: document.querySelector('input[name="accessory_foot_pedal"]'),
        accessory_case: document.querySelector('input[name="accessory_case"]'),
    };

    let lastSerial = '';

    function fillProfile(profile) {
        Object.entries(fields).forEach(([key, element]) => {
            if (!element) { return; }
            element.value = profile[key] ?? '';
        });

        Object.entries(checks).forEach(([key, element]) => {
            if (!element) { return; }
            element.checked = Boolean(profile[key]);
        });
    }

    async function lookupSerial() {
        const serial = serialInput.value.trim();
        if (!serial || serial === lastSerial) { return; }

        lastSerial = serial;
        hint.textContent = 'Seriennummer wird geprueft ...';

        try {
            const response = await fetch(`{{ route('geiser-portal.machine-profiles.lookup') }}?serial_number=${encodeURIComponent(serial)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();

            if (payload.found && payload.profile) {
                fillProfile(payload.profile);
                hint.textContent = 'Vorhandene Daten zur Seriennummer wurden geladen.';
                return;
            }

            hint.textContent = 'Zu dieser Seriennummer wurde noch keine Maschinenakte gefunden.';
        } catch (error) {
            hint.textContent = 'Seriennummer konnte gerade nicht geprueft werden.';
        }
    }

    serialInput.addEventListener('change', lookupSerial);
    serialInput.addEventListener('blur', lookupSerial);
})();
</script>
@endpush

```
