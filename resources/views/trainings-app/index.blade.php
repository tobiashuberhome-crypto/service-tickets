@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Trainings-App</h1>
    <p class="muted">Kundenportal-Tickets mit Status-Verwaltung</p>
</div>

{{-- Filter --}}
<form method="get" class="filter-bar" style="margin-bottom:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
    <select name="status" class="form-control" style="width:auto;">
        <option value="">Alle Status</option>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn">Filtern</button>
    @if(request()->has('status'))
        <a href="{{ route('trainings-app.index') }}" class="btn secondary">Zurücksetzen</a>
    @endif
</form>

<div class="card" style="overflow-x:auto;">
    <table class="data-table" style="width:100%;">
        <thead>
            <tr>
                <th>Ticket-Nr.</th>
                <th>Rolle</th>
                <th>Typ</th>
                <th>Titel</th>
                <th>Kunde</th>
                <th>Status</th>
                <th>Erstellt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td><code>{{ $ticket->ticket_number }}</code></td>
                <td>
                    @php
                        $note = (string) $ticket->technician_note;
                        if (str_contains($note, 'superadmin')) {
                            echo 'Superadmin';
                        } elseif (str_contains($note, 'admin')) {
                            echo 'Admin';
                        } else {
                            echo 'Member';
                        }
                    @endphp
                </td>
                <td style="text-transform:capitalize;">
                    {{ str_contains((string) $ticket->technician_note, 'bug') ? 'BUG' : 'Feature' }}
                </td>
                <td>
                    <strong>
                        @php
                            $note = (string) $ticket->technician_note;
                            $parts = explode(' / ', $note);
                            echo $parts[2] ?? 'Ohne Titel';
                        @endphp
                    </strong>
                    @if($ticket->error_description)
                        <div style="font-size:.85em; color:#666; margin-top:.2rem; white-space:pre-wrap; word-break:break-word; max-height:80px; overflow:hidden;">{!! e($ticket->error_description) !!}</div>
                    @endif
                    @if($ticket->messages->isNotEmpty())
                        <div style="margin-top:.4rem; font-size:.85em;">
                            @foreach($ticket->messages as $message)
                                @if($message->attachments->isNotEmpty())
                                    <div style="margin-top:.2rem;">
                                        <strong style="color:#555;">Anhänge:</strong>
                                        <div style="display:flex; gap:.3rem; flex-wrap:wrap; margin-top:.2rem;">
                                            @foreach($message->attachments as $attachment)
                                                <a href="{{ route('tickets.messages.attachments.download', [$ticket, $message, $attachment]) }}" style="font-size:.8em; padding:.2rem .4rem; background:#f0f0f0; border-radius:4px; text-decoration:none; color:#0066cc;">
                                                    📎 {{ $attachment->original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </td>
                <td>{{ $ticket->customer_name_snapshot }}</td>
                <td>
                    <form method="post" action="{{ route('trainings-app.update-status', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-control" style="font-size:.85em;" onchange="this.form.submit()">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </td>
                <td style="white-space:nowrap; font-size:.85em;">
                    {{ $ticket->created_at->format('d.m.Y H:i') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:#888; padding:2rem;">
                    Keine Trainings-App-Tickets gefunden.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1rem;">
    {{ $tickets->links() }}
</div>
@endsection
