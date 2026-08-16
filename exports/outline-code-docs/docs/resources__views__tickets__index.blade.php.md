# Datei: resources\views\tickets\index.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\tickets\index.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
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
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Tickets</h1>
            <p class="muted">Service- und Reparaturauftraege nach Kalenderwochen, Frist und manueller Reihenfolge.</p>
        </div>
        <div class="button-row">
            <form id="delivery-note-form" method="post" action="{{ route('tickets.delivery-note') }}">
                @csrf
                <button class="btn secondary" type="button" id="select-all-tickets">Alle markieren</button>
                <button class="btn" type="submit">Lieferschein erstellen</button>
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
    </form>

    <div style="height: 18px;"></div>

    <div id="ticket-board" class="ticket-board" data-reorder-url="{{ route('tickets.reorder') }}">
        @forelse ($weekGroups as $week)
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
        @empty
            @if ($withoutTargetDate->isEmpty())
                <div class="panel panel-body">Noch keine Tickets vorhanden.</div>
            @endif
        @endforelse

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
@endsection

@push('scripts')
<script>
(() => {
    const board = document.getElementById('ticket-board');
    if (!board) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let dragged = null;

    board.querySelectorAll('.ticket-card').forEach((card) => {
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
    });

    board.querySelectorAll('.ticket-lane').forEach((lane) => {
        lane.addEventListener('dragover', (event) => {
            event.preventDefault();
            const after = getCardAfter(lane, event.clientX);
            if (!dragged) {
                return;
            }
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
    });

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
})();

(() => {
    const selectAllButton = document.getElementById('select-all-tickets');
    if (!selectAllButton) {
        return;
    }

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

```
