@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const byId = (id) => document.getElementById(id);

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            ...options,
        });

        if (!response.ok) {
            const text = await response.text();
            throw new Error(text || 'Anfrage fehlgeschlagen');
        }

        return response.json();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderError(target, message) {
        if (!target) return;
        target.innerHTML = `<div class="alert error">${escapeHtml(message)}</div>`;
    }

    const serialInput = byId('serial_number');
    const serialHistoryHint = byId('serial_history_hint');
    const serialHistoryDialog = byId('serial-history-dialog');
    const serialHistoryDialogSubtitle = byId('serial-history-dialog-subtitle');
    const serialHistoryDialogBody = byId('serial-history-dialog-body');
    const serialHistoryDialogClose = byId('serial-history-dialog-close');
    let serialLookupTimer = null;
    let serialLookupToken = 0;
    let lastSerialLookup = '';

    function renderSerialHistoryDialog(history) {
        if (!serialHistoryDialogSubtitle || !serialHistoryDialogBody) {
            return;
        }

        serialHistoryDialogSubtitle.textContent = `Seriennummer: ${history?.serial_number || '-'}`;

        if (!history || !history.count) {
            serialHistoryDialogBody.innerHTML = '<tr><td colspan="7" class="muted">Zu dieser Seriennummer wurden noch keine Tickets gefunden.</td></tr>';
            return;
        }

        serialHistoryDialogBody.innerHTML = history.tickets.map((ticket) => `
            <tr>
                <td><a href="${escapeHtml(ticket.url)}">${escapeHtml(ticket.ticket_number)}</a></td>
                <td>${escapeHtml(ticket.status_label)}</td>
                <td>${escapeHtml(ticket.acceptance_date || '-')}</td>
                <td>${escapeHtml(ticket.machine_label || '-')}</td>
                <td>${escapeHtml(ticket.customer_name || '-')}</td>
                <td>${escapeHtml(ticket.contact_name || '-')}</td>
                <td>${escapeHtml(ticket.created_at || '-')}</td>
            </tr>
        `).join('');
    }

    function updateSerialHistoryHint(history) {
        if (!serialHistoryHint) {
            return;
        }

        if (!history || !history.count) {
            serialHistoryHint.style.display = 'none';
            serialHistoryHint.innerHTML = '';
            renderSerialHistoryDialog(history);
            return;
        }

        const lastDate = history.last_acceptance_date ? ` Letzte Annahme: <strong>${escapeHtml(history.last_acceptance_date)}</strong>.` : '';
        serialHistoryHint.style.display = 'block';
        serialHistoryHint.innerHTML = `
            Zu dieser Seriennummer gibt es bereits <strong>${history.count} Ticket(s)</strong>.${lastDate}
            <button type="button" class="btn secondary" id="open-serial-history" style="margin-left:10px;">Historie anzeigen</button>
        `;
        renderSerialHistoryDialog(history);
        byId('open-serial-history')?.addEventListener('click', () => serialHistoryDialog?.showModal());
    }

    async function lookupSerialHistory(force = false) {
        if (!serialInput || serialInput.readOnly) {
            return;
        }

        const serial = serialInput.value.trim();
        if (!serial) {
            lastSerialLookup = '';
            updateSerialHistoryHint(null);
            return;
        }

        if (!force && serial === lastSerialLookup) {
            return;
        }

        lastSerialLookup = serial;
        const requestToken = ++serialLookupToken;

        try {
            const url = new URL('{{ route('lookup.ticket-history') }}', window.location.origin);
            url.searchParams.set('serial_number', serial);
            const payload = await requestJson(url);

            if (requestToken !== serialLookupToken) {
                return;
            }

            updateSerialHistoryHint(payload.history || null);
        } catch (error) {
            if (requestToken !== serialLookupToken) {
                return;
            }

            if (serialHistoryHint) {
                serialHistoryHint.style.display = 'block';
                serialHistoryHint.innerHTML = `<div class="alert error">${escapeHtml('Ticket-Historie konnte nicht geladen werden.')}</div>`;
            }
        }
    }

    function selectCustomer(customer) {
        byId('dolibarr_customer_id').value = customer.id;
        byId('customer_name_snapshot').value = customer.name;
        byId('customer_query').value = customer.name;
        const selected = byId('customer_selected');
        selected.style.display = '';
        selected.textContent = `${customer.name} (ID ${customer.id})`;
        byId('customer_results').innerHTML = '';
    }

    function selectMachine(machine) {
        byId('dolibarr_machine_product_id').value = machine.id;
        byId('manufacturer_snapshot').value = machine.manufacturer || byId('machine_manufacturer_query').value;
        byId('machine_ref_snapshot').value = machine.ref;
        byId('machine_manufacturer_query').value = machine.manufacturer || byId('machine_manufacturer_query').value;
        byId('machine_ref_query').value = machine.ref;
        const selected = byId('machine_selected');
        selected.style.display = '';
        selected.textContent = `${byId('manufacturer_snapshot').value || '-'} / ${machine.ref} (Produkt-ID ${machine.id})`;
        byId('machine_results').innerHTML = '';
    }

    function syncMachineFields() {
        const manufacturer = byId('machine_manufacturer_query');
        const ref = byId('machine_ref_query');
        const manufacturerSnapshot = byId('manufacturer_snapshot');
        const refSnapshot = byId('machine_ref_snapshot');
        const selected = byId('machine_selected');

        if (manufacturer && manufacturerSnapshot) {
            manufacturerSnapshot.value = manufacturer.value;
        }

        if (ref && refSnapshot) {
            refSnapshot.value = ref.value;
        }

        if (selected && byId('dolibarr_machine_product_id')?.value) {
            selected.textContent = `${manufacturerSnapshot?.value || '-'} / ${refSnapshot?.value || '-'} (Produkt-ID ${byId('dolibarr_machine_product_id').value})`;
            selected.style.display = '';
        }
    }

    async function loadManufacturers() {
        const select = byId('machine_manufacturer_query');
        if (!select || select.disabled) return;

        const selected = select.value || byId('manufacturer_snapshot').value;

        try {
            const manufacturers = await requestJson('{{ route('lookup.manufacturers') }}');
            select.innerHTML = '<option value="">Alle Hersteller</option>';

            if (selected && !manufacturers.some((manufacturer) => manufacturer.toLowerCase() === selected.toLowerCase())) {
                const option = document.createElement('option');
                option.value = selected;
                option.textContent = selected;
                select.appendChild(option);
            }

            manufacturers.forEach((manufacturer) => {
                const option = document.createElement('option');
                option.value = manufacturer;
                option.textContent = manufacturer;
                select.appendChild(option);
            });

            select.value = selected;
        } catch (error) {
            const option = document.createElement('option');
            option.value = selected;
            option.textContent = selected || 'Hersteller konnten nicht geladen werden';
            select.appendChild(option);
            select.value = selected;
        }
    }

    const customerSearchBtn = byId('customer_search_btn');
    if (customerSearchBtn) {
        customerSearchBtn.addEventListener('click', async () => {
            const target = byId('customer_results');
            target.innerHTML = '<div class="muted">Suche laeuft...</div>';
            try {
                const url = new URL('{{ route('lookup.customers') }}', window.location.origin);
                url.searchParams.set('q', byId('customer_query').value);
                const customers = await requestJson(url);
                target.innerHTML = '';
                if (!customers.length) {
                    target.innerHTML = '<div class="muted">Kein Kunde gefunden.</div>';
                    return;
                }
                customers.forEach((customer) => {
                    const row = document.createElement('div');
                    row.className = 'lookup-item';
                    row.innerHTML = `<div><strong>${escapeHtml(customer.name)}</strong><div class="muted">${escapeHtml(customer.zip || '')} ${escapeHtml(customer.town || '')}</div></div>`;
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn secondary';
                    button.textContent = 'Auswaehlen';
                    button.addEventListener('click', () => selectCustomer(customer));
                    row.appendChild(button);
                    target.appendChild(row);
                });
            } catch (error) {
                renderError(target, error.message);
            }
        });
    }

    const customerCreateBtn = byId('customer_create_btn');
    if (customerCreateBtn) {
        customerCreateBtn.addEventListener('click', async () => {
            const target = byId('customer_results');
            target.innerHTML = '<div class="muted">Kunde wird angelegt...</div>';
            try {
                const customer = await requestJson('{{ route('lookup.customers.create') }}', {
                    method: 'POST',
                    body: JSON.stringify({
                        name: byId('new_customer_name').value,
                        email: byId('new_customer_email').value,
                        zip: byId('new_customer_zip').value,
                        town: byId('new_customer_town').value,
                        address: byId('new_customer_address').value,
                    }),
                });
                selectCustomer(customer);
            } catch (error) {
                renderError(target, error.message);
            }
        });
    }

    const machineSearchBtn = byId('machine_search_btn');
    if (machineSearchBtn) {
        machineSearchBtn.addEventListener('click', async () => {
            const target = byId('machine_results');
            target.innerHTML = '<div class="muted">Suche laeuft...</div>';
            try {
                const url = new URL('{{ route('lookup.machines') }}', window.location.origin);
                url.searchParams.set('manufacturer', byId('machine_manufacturer_query').value);
                url.searchParams.set('ref', byId('machine_ref_query').value);
                const machines = await requestJson(url);
                target.innerHTML = '';
                if (!machines.length) {
                    target.innerHTML = '<div class="muted">Keine Maschine gefunden.</div>';
                    return;
                }
                machines.forEach((machine) => {
                    const row = document.createElement('div');
                    row.className = 'lookup-item';
                    row.innerHTML = `<div><strong>${escapeHtml(machine.ref)}</strong><div class="muted">${(machine.manufacturer ? escapeHtml(machine.manufacturer) : '<em>(kein Hersteller)</em>')} ${escapeHtml(machine.label || '')}</div></div>`;
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn secondary';
                    button.textContent = 'Auswaehlen';
                    button.addEventListener('click', () => selectMachine(machine));
                    row.appendChild(button);
                    target.appendChild(row);
                });
            } catch (error) {
                renderError(target, error.message);
            }
        });
    }

    const machineCreateBtn = byId('machine_create_btn');
    if (machineCreateBtn) {
        machineCreateBtn.addEventListener('click', async () => {
            const target = byId('machine_results');
            target.innerHTML = '<div class="muted">Maschine wird angelegt...</div>';
            try {
                const machine = await requestJson('{{ route('lookup.machines.create') }}', {
                    method: 'POST',
                    body: JSON.stringify({
                        manufacturer: byId('new_machine_manufacturer').value,
                        ref: byId('new_machine_ref').value,
                        label: byId('new_machine_label').value,
                    }),
                });
                selectMachine(machine);
            } catch (error) {
                renderError(target, error.message);
            }
        });
    }

    const repair = byId('repair_enabled');
    const errorWrap = byId('error_description_wrap');
    function toggleErrorDescription() {
        if (!repair || !errorWrap) return;
        errorWrap.style.display = repair.checked ? '' : 'none';
    }
    if (repair) {
        repair.addEventListener('change', toggleErrorDescription);
        toggleErrorDescription();
    }

    const manufacturerSelect = byId('machine_manufacturer_query');
    if (manufacturerSelect) {
        manufacturerSelect.addEventListener('change', () => {
            const value = manufacturerSelect.value;
            syncMachineFields();
            const newMachineManufacturer = byId('new_machine_manufacturer');
            if (newMachineManufacturer && !newMachineManufacturer.value) {
                newMachineManufacturer.value = value;
            }
        });
        loadManufacturers();
    }

    const machineRefInput = byId('machine_ref_query');
    if (machineRefInput) {
        machineRefInput.addEventListener('input', syncMachineFields);
        machineRefInput.addEventListener('change', syncMachineFields);
    }

    const ticketForms = document.querySelectorAll('form[action*="/tickets"]');
    ticketForms.forEach((form) => {
        form.addEventListener('submit', syncMachineFields);
    });

    if (serialInput && !serialInput.readOnly) {
        serialInput.addEventListener('input', () => {
            window.clearTimeout(serialLookupTimer);
            serialLookupTimer = window.setTimeout(() => lookupSerialHistory(), 350);
        });
        serialInput.addEventListener('change', () => lookupSerialHistory(true));
        serialInput.addEventListener('blur', () => lookupSerialHistory(true));

        if (serialInput.value.trim() !== '') {
            lookupSerialHistory(true);
        }
    }

    serialHistoryDialogClose?.addEventListener('click', () => serialHistoryDialog?.close());
    serialHistoryDialog?.addEventListener('click', (event) => {
        const rect = serialHistoryDialog.getBoundingClientRect();
        const clickedInDialog = rect.top <= event.clientY
            && event.clientY <= rect.top + rect.height
            && rect.left <= event.clientX
            && event.clientX <= rect.left + rect.width;

        if (!clickedInDialog) {
            serialHistoryDialog.close();
        }
    });
})();
</script>
@endpush
