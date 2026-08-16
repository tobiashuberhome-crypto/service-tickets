@extends('layouts.customer-portal-cibena')

@section('content')
    <div class="page-header">
        <div>
            <h1>Ticket-Historie</h1>
            <p class="muted">Suchen Sie nach einer Seriennummer, um fruehere Tickets anzuzeigen und direkt zu oeffnen.</p>
        </div>
        <a class="btn secondary" href="{{ route('cibena-portal.dashboard') }}">Zurueck</a>
    </div>

    <div class="panel panel-body stack" style="max-width: 980px;">
        <form id="history-search-form" class="stack">
            <div class="grid grid-2">
                <div>
                    <label for="history_serial_number">Seriennummer</label>
                    <input id="history_serial_number" name="serial_number" value="{{ $initialSerialNumber }}" placeholder="Seriennummer eingeben">
                </div>
                <div class="button-row" style="align-items: end;">
                    <button class="btn" type="submit">Historie suchen</button>
                </div>
            </div>
        </form>

        <p class="muted" id="history-search-hint">Geben Sie eine Seriennummer ein, um die vorhandenen Tickets zu laden.</p>

        <div id="history-results-wrap" style="display:none;">
            <div class="alert alert-info" id="history-results-summary" style="display:none;"></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Status</th>
                            <th>Annahmedatum</th>
                            <th>Maschine</th>
                            <th>Ansprechpartner</th>
                            <th>Erstellt</th>
                        </tr>
                    </thead>
                    <tbody id="history-results-body"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('history-search-form');
    const serialInput = document.getElementById('history_serial_number');
    const hint = document.getElementById('history-search-hint');
    const resultsWrap = document.getElementById('history-results-wrap');
    const summary = document.getElementById('history-results-summary');
    const body = document.getElementById('history-results-body');

    if (!form || !serialInput || !hint || !resultsWrap || !summary || !body) {
        return;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderHistory(history) {
        if (!history || !history.count) {
            resultsWrap.style.display = 'none';
            summary.style.display = 'none';
            body.innerHTML = '';
            hint.textContent = 'Zu dieser Seriennummer wurden noch keine Tickets gefunden.';
            return;
        }

        const lastDate = history.last_acceptance_date ? ` Letzte Annahme: ${history.last_acceptance_date}.` : '';
        hint.textContent = '';
        summary.style.display = 'block';
        summary.textContent = `Es wurden ${history.count} Ticket(s) gefunden.${lastDate}`;
        body.innerHTML = history.tickets.map((ticket) => `
            <tr>
                <td>
                    <a href="${escapeHtml(ticket.url)}" target="_blank" rel="noopener">${escapeHtml(ticket.ticket_number)}</a>
                    ${ticket.created_via_customer_portal ? '' : '<span class="badge bg-info">vom Techniker erstellt</span>'}
                </td>
                <td>${escapeHtml(ticket.status_label)}</td>
                <td>${escapeHtml(ticket.acceptance_date || '-')}</td>
                <td>${escapeHtml(ticket.machine_label || '-')}</td>
                <td>${escapeHtml(ticket.contact_name || '-')}</td>
                <td>${escapeHtml(ticket.created_at || '-')}</td>
            </tr>
        `).join('');
        resultsWrap.style.display = 'block';
    }

    async function searchHistory(event) {
        if (event) {
            event.preventDefault();
        }

        const serial = serialInput.value.trim();
        if (!serial) {
            resultsWrap.style.display = 'none';
            summary.style.display = 'none';
            body.innerHTML = '';
            hint.textContent = 'Bitte zuerst eine Seriennummer eingeben.';
            return;
        }

        hint.textContent = 'Historie wird geladen ...';

        try {
            const response = await fetch(`{{ route('cibena-portal.ticket-history.lookup') }}?serial_number=${encodeURIComponent(serial)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            renderHistory(payload.history);
        } catch (error) {
            resultsWrap.style.display = 'none';
            summary.style.display = 'none';
            body.innerHTML = '';
            hint.textContent = 'Historie konnte gerade nicht geladen werden.';
        }
    }

    form.addEventListener('submit', searchHistory);

    if (serialInput.value.trim() !== '') {
        searchHistory();
    }
})();
</script>
@endpush
