@extends('layouts.app')

@php
    use Illuminate\Support\Carbon;

    $ticketCardClass = function ($ticket): string {
        if (! $ticket->target_date) {
            return 'ticket-card-neutral';
        }

        $days = Carbon::today()->diffInDays($ticket->target_date->copy()->startOfDay(), false);

        if ($days <= 1) {
            return 'ticket-card-danger';
        }

        if ($days <= 3) {
            return 'ticket-card-warning';
        }

        return 'ticket-card-success';
    };

    $renderedWeekKeys = collect();
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Tickets</h1>
            <p class="muted">Service- und Reparaturauftraege nach Quellen und Kalenderwochen.</p>
        </div>
        <div class="button-row">
<<<<<<< HEAD
            <form id="delivery-note-form" method="post" action="{{ route('tickets.delivery-note') }}">
                @csrf
                <button class="btn secondary" type="button" id="select-all-tickets">Alle markieren</button>
                <button class="btn" type="submit">Lieferschein erstellen</button>
=======
            <form id="ticket-selection-form" method="post">
                @csrf
                <button class="btn secondary" type="button" id="select-all-tickets">Alle markieren</button>
                <button class="btn secondary" type="submit" formaction="{{ route('tickets.delivery-note') }}">Lieferschein erstellen</button>
                <button class="btn" type="submit" formaction="{{ route('tickets.monthly-invoice') }}">Monatsrechnung erstellen</button>
>>>>>>> old-ticket-system/main
            </form>
            <a class="btn" href="{{ route('tickets.create') }}">Neues Ticket</a>
        </div>
    </div>

    <form class="panel panel-body" method="get" action="{{ route('tickets.index') }}">
        <div class="grid grid-3">
            <div>
                <label for="q">Suche</label>
                <input id="q" name="q" value="{{ $search }}" placeholder="Ticket, Kunde, Maschine, Seriennummer">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Alle</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="button-row" style="align-items: end;">
                <button class="btn" type="submit">Filtern</button>
                <a class="btn secondary" href="{{ route('tickets.index') }}">Zuruecksetzen</a>
            </div>
        </div>
        <div style="margin-top: 8px;">
            <label class="check-row">
                <input type="checkbox" name="hide_returned" value="1" @checked($hideReturned) onchange="this.form.submit()">
                Ausgegebene Maschinen ausblenden
            </label>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <div id="ticket-board" class="ticket-board" data-reorder-url="{{ route('tickets.reorder') }}">
        {{-- Eingangskacheln --}}
        <section class="ticket-week panel" data-week-start="">
            <div class="ticket-week-head">
                <h2>Schul-Portal</h2>
                <span class="muted">{{ $schoolPortalIncoming->count() }} Ticket(s)</span>
            </div>
            <div class="ticket-lane" data-week-start="">
                @foreach ($schoolPortalIncoming as $ticket)
                    @include('tickets.partials.card', ['ticket' => $ticket, 'cardClass' => $ticketCardClass($ticket)])
                @endforeach
            </div>
        </section>

        <section class="ticket-week panel" data-week-start="">
            <div class="ticket-week-head">
                <h2>EasyAppointments</h2>
                <span class="muted">{{ $easyAppointmentsIncoming->count() }} Ticket(s)</span>
            </div>
            <div class="ticket-lane" data-week-start="">
                @foreach ($easyAppointmentsIncoming as $ticket)
                    @include('tickets.partials.card', ['ticket' => $ticket, 'cardClass' => $ticketCardClass($ticket)])
                @endforeach
            </div>
        </section>

        {{-- Immer die naechsten 3 KWs anzeigen --}}
        @foreach ($upcomingWeeks as $week)
            @php
                $ticketsForWeek = $weekGroups[$week['key']]['tickets'] ?? collect();
                $renderedWeekKeys->push($week['key']);
            @endphp
            <section class="ticket-week panel" data-week-start="{{ $week['key'] }}">
                <div class="ticket-week-head">
                    <h2>{{ $week['label'] }}</h2>
                    <span class="muted">{{ $ticketsForWeek->count() }} Ticket(s)</span>
                </div>
                <div class="ticket-lane" data-week-start="{{ $week['key'] }}">
                    @foreach ($ticketsForWeek as $ticket)
                        @include('tickets.partials.card', ['ticket' => $ticket, 'cardClass' => $ticketCardClass($ticket)])
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- Weitere vorhandene Wochen mit Tickets anzeigen --}}
        @foreach ($weekGroups as $week)
            @continue($renderedWeekKeys->contains($week['key']))
            <section class="ticket-week panel" data-week-start="{{ $week['key'] }}">
                <div class="ticket-week-head">
                    <h2>{{ $week['label'] }}</h2>
                    <span class="muted">{{ $week['tickets']->count() }} Ticket(s)</span>
                </div>
                <div class="ticket-lane" data-week-start="{{ $week['key'] }}">
                    @foreach ($week['tickets'] as $ticket)
                        @include('tickets.partials.card', ['ticket' => $ticket, 'cardClass' => $ticketCardClass($ticket)])
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="ticket-week panel" data-week-start="">
            <div class="ticket-week-head">
                <h2>Ohne Frist</h2>
                <span class="muted">{{ $withoutTargetDate->count() }} Ticket(s)</span>
            </div>
            <div class="ticket-lane" data-week-start="">
                @foreach ($withoutTargetDate as $ticket)
                    @include('tickets.partials.card', ['ticket' => $ticket, 'cardClass' => $ticketCardClass($ticket)])
                @endforeach
            </div>
        </section>
    </div>

<<<<<<< HEAD
=======
    <div class="panel" style="margin-top: 2rem;">
        <div class="page-header" style="margin-bottom: 1rem;">
            <div>
                <h2>Tickets nach Monat</h2>
                <p class="muted">Monatliche Übersicht mit Auswahl für gemeinsame Rechnungen.</p>
            </div>
        </div>

        @forelse ($monthGroups as $month)
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 .75rem;">{{ $month['label'] }}</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th style="width: 30px;"></th>
                            <th>Ticket</th>
                            <th>Kunde</th>
                            <th>Maschine</th>
                            <th>Seriennummer</th>
                            <th>Datum</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($month['tickets'] as $ticket)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ticket_ids[]" value="{{ $ticket->id }}" class="delivery-note-checkbox" form="ticket-selection-form">
                                </td>
                                <td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</a></td>
                                <td>{{ $ticket->customer_name_snapshot }}</td>
                                <td>{{ $ticket->customerMachine?->manufacturer_snapshot }} / {{ $ticket->customerMachine?->machine_ref_snapshot }}</td>
                                <td>{{ $ticket->customerMachine?->serial_number ?: '-' }}</td>
                                <td>{{ $ticket->acceptance_date?->format('d.m.Y') ?: $ticket->created_at?->format('d.m.Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="muted">Keine Tickets in dieser Ansicht.</p>
        @endforelse
    </div>

>>>>>>> old-ticket-system/main
    <div style="margin-top:1rem;">
        <button type="button" id="add-week-column" class="btn secondary">+ Weitere KW</button>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const board = document.getElementById('ticket-board');
    if (!board) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let dragged = null;

    function bindCard(card) {
        if (card.dataset.boundDrag === '1') return;
        card.dataset.boundDrag = '1';

        card.addEventListener('dragstart', (event) => {
            dragged = card;
            card.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.ticketId);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            dragged = null;
        });
    }

    function bindLane(lane) {
        if (lane.dataset.boundLane === '1') return;
        lane.dataset.boundLane = '1';

        lane.addEventListener('dragover', (event) => {
            event.preventDefault();
            const after = getCardAfter(lane, event.clientX);
            if (!dragged) return;
            if (after == null) {
                lane.appendChild(dragged);
            } else {
                lane.insertBefore(dragged, after);
            }
        });

        lane.addEventListener('drop', async (event) => {
            event.preventDefault();
            await saveLane(lane);
        });
    }

    board.querySelectorAll('.ticket-card').forEach(bindCard);
    board.querySelectorAll('.ticket-lane').forEach(bindLane);

    function getCardAfter(lane, x) {
        return [...lane.querySelectorAll('.ticket-card:not(.dragging)')]
            .reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = x - box.left - box.width / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                }
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    async function saveLane(lane) {
        const ids = [...lane.querySelectorAll('.ticket-card')].map((card) => card.dataset.ticketId);
        const response = await fetch(board.dataset.reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                week_start: lane.dataset.weekStart || null,
                ticket_ids: ids,
            }),
        });

        if (!response.ok) {
            window.location.reload();
        }
    }

    function weekLabel(weekStartDate) {
        const start = new Date(weekStartDate + 'T00:00:00');
        const end = new Date(start);
        end.setDate(start.getDate() + 6);

        const kw = getISOWeek(start);
        const dd = (d) => String(d.getDate()).padStart(2, '0');
        const mm = (d) => String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = (d) => d.getFullYear();

        return `KW ${kw} / ${dd(start)}.${mm(start)} - ${dd(end)}.${mm(end)}.${yyyy(end)}`;
    }

    function getISOWeek(date) {
        const target = new Date(date.valueOf());
        const dayNr = (date.getDay() + 6) % 7;
        target.setDate(target.getDate() - dayNr + 3);
        const firstThursday = new Date(target.getFullYear(), 0, 4);
        const firstDayNr = (firstThursday.getDay() + 6) % 7;
        firstThursday.setDate(firstThursday.getDate() - firstDayNr + 3);
        return 1 + Math.round((target - firstThursday) / 604800000);
    }

    function toMonday(date) {
        const d = new Date(date.valueOf());
        const day = (d.getDay() + 6) % 7;
        d.setDate(d.getDate() - day);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function dateKey(date) {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    document.getElementById('add-week-column')?.addEventListener('click', () => {
        const weekStarts = [...board.querySelectorAll('.ticket-week[data-week-start]')]
            .map((el) => el.dataset.weekStart)
            .filter(Boolean)
            .sort();

        const last = weekStarts.length ? new Date(weekStarts[weekStarts.length - 1] + 'T00:00:00') : toMonday(new Date());
        const next = toMonday(last);
        next.setDate(next.getDate() + 7);
        const nextKey = dateKey(next);

        const section = document.createElement('section');
        section.className = 'ticket-week panel';
        section.dataset.weekStart = nextKey;

        section.innerHTML = `
            <div class="ticket-week-head">
                <h2>${weekLabel(nextKey)}</h2>
                <span class="muted">0 Ticket(s)</span>
            </div>
            <div class="ticket-lane" data-week-start="${nextKey}"></div>
        `;

        board.appendChild(section);
        const lane = section.querySelector('.ticket-lane');
        bindLane(lane);
    });
})();

(() => {
    const selectAllButton = document.getElementById('select-all-tickets');
    if (!selectAllButton) return;

    selectAllButton.addEventListener('click', () => {
        const checkboxes = Array.from(document.querySelectorAll('.delivery-note-checkbox'));
        const markAll = checkboxes.some((checkbox) => !checkbox.checked);
        checkboxes.forEach((checkbox) => {
            checkbox.checked = markAll;
        });
        selectAllButton.textContent = markAll ? 'Auswahl aufheben' : 'Alle markieren';
    });
})();
</script>
@endpush