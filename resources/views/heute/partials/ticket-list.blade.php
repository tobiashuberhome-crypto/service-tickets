@forelse ($tickets as $ticket)
    <div class="today-item">
        <a class="today-item-main" href="{{ route('tickets.show', $ticket) }}">
            <div>
                <strong>{{ $ticket->dolibarr_order_ref ?: $ticket->ticket_number }}</strong>
                <span class="badge {{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
                @if ($ticket->thss)
                    <span class="badge" style="background:#7c3aed; color:#fff;">THSS</span>
                @endif
            </div>
            <div>{{ $ticket->customer_name_snapshot }}</div>
            @if ($ticket->customerMachine)
                <div class="muted">
                    {{ $ticket->customerMachine->manufacturer_snapshot }} / {{ $ticket->customerMachine->machine_ref_snapshot }}
                </div>
            @endif
            @if (! empty($showDue) && $ticket->target_date)
                @php $days = (int) $today->diffInDays($ticket->target_date->copy()->startOfDay(), false); @endphp
                <div class="{{ $days < 0 ? 'today-overdue' : '' }}">
                    Frist {{ $ticket->target_date->format('d.m.Y') }}
                    ({{ $days < 0 ? abs($days).' Tag(e) überfällig' : ($days === 0 ? 'heute' : 'in '.$days.' Tag(en)') }})
                </div>
            @elseif ($ticket->acceptance_date)
                <div class="muted">Annahme {{ $ticket->acceptance_date->format('d.m.Y') }}</div>
            @endif
            @if (! empty($showRetry) && $ticket->sync_message)
                <div class="muted">{{ \Illuminate\Support\Str::limit($ticket->sync_message, 140) }}</div>
            @endif
        </a>
        @if (! empty($showRetry))
            <form method="post" action="{{ route('tickets.retry-sync', $ticket) }}">
                @csrf
                <button class="btn secondary" type="submit">Erneut senden</button>
            </form>
        @endif
    </div>
@empty
    <p class="muted">{{ $emptyText }}</p>
@endforelse
@if ($total > $tickets->count())
    <p class="muted">… und {{ $total - $tickets->count() }} weitere. <a href="{{ route('tickets.index') }}">Zum Board</a></p>
@endif
