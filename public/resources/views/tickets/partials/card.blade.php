<div style="position: relative;">
    <label style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); z-index: 3; background: #fff; border-radius: 4px; padding: 4px; display: flex; align-items: center; justify-content: center;">
        <input
            type="checkbox"
            name="ticket_ids[]"
            value="{{ $ticket->id }}"
            class="delivery-note-checkbox"
            form="ticket-selection-form"
            onclick="event.stopPropagation();"
            onmousedown="event.stopPropagation();"
            style="width: 16px; height: 16px; margin: 0;"
        >
    </label>
    <a class="ticket-card {{ $cardClass }}" href="{{ route('tickets.show', $ticket) }}" draggable="true" data-ticket-id="{{ $ticket->id }}" style="padding-left: 38px;">
        <div class="ticket-card-head">
            <span class="ticket-number">{{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</span>
            <span class="badge {{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
        </div>
        <div>
            <strong>{{ $ticket->customer_name_snapshot }}</strong>
            <div class="muted">{{ $ticket->customerMachine?->manufacturer_snapshot }} / {{ $ticket->customerMachine?->machine_ref_snapshot }}</div>
            @if ($ticket->customerMachine?->serial_number)
                <div class="muted">SN {{ $ticket->customerMachine->serial_number }}</div>
            @endif
        </div>
        <div class="grid grid-2">
            <div>
                <span class="muted">Annahme</span><br>
                {{ $ticket->acceptance_date?->format('d.m.Y') }}
            </div>
            <div>
                <span class="muted">Frist</span><br>
                {{ $ticket->target_date?->format('d.m.Y') ?: '-' }}
            </div>
        </div>
        <div class="button-row">
            <span class="badge {{ $ticket->sync_status }}">{{ $ticket->syncStatusLabel() }}</span>
            @if ($ticket->machine_returned)
                <span class="badge" style="background:#16a34a; color:#fff;">✓ Ausgegeben</span>
            @endif
            @if ($ticket->spare_part_order_required)
                <span class="badge order-required">Ersatzteilbestellung</span>
            @endif
            @if ($ticket->created_via_customer_portal)
                <span class="badge">Kundenportal</span>
            @endif
        </div>
    </a>
</div>
