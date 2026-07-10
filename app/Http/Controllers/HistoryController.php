<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketPart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;
        $serialNumber = trim((string) $request->query('serial_number'));

        $partsTurnover = TicketPart::query()
            ->select([
                'part_ref_snapshot',
                'label_snapshot',
                DB::raw('sum(quantity) as consumed_quantity'),
                DB::raw('count(*) as position_count'),
            ])
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('part_ref_snapshot', 'label_snapshot')
            ->orderByDesc('consumed_quantity')
            ->limit(100)
            ->get();

        $serviceMachines = $this->machineTypeStats(true, false, $from, $to);
        $repairMachines = $this->machineTypeStats(false, true, $from, $to);
        $serialHistory = $serialNumber !== '' ? $this->serialHistoryData($serialNumber) : null;

        return view('history.index', [
            'partsTurnover' => $partsTurnover,
            'serviceMachines' => $serviceMachines,
            'repairMachines' => $repairMachines,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'serialNumber' => $serialNumber,
            'serialHistory' => $serialHistory,
        ]);
    }

    public function lookupSerialHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:255'],
        ]);

        return response()->json($this->serialHistoryData(trim($data['serial_number'])));
    }

    private function machineTypeStats(bool $service, bool $repair, ?Carbon $from, ?Carbon $to)
    {
        return Ticket::query()
            ->join('customer_machines', 'tickets.customer_machine_id', '=', 'customer_machines.id')
            ->select([
                DB::raw("coalesce(customer_machines.machine_ref_snapshot, 'Unbekannt') as machine_type"),
                DB::raw('count(*) as ticket_count'),
            ])
            ->when($service, fn ($query) => $query->where('tickets.service_enabled', true))
            ->when($repair, fn ($query) => $query->where('tickets.repair_enabled', true))
            ->when($from, fn ($query) => $query->where('tickets.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('tickets.created_at', '<=', $to))
            ->groupBy('customer_machines.machine_ref_snapshot')
            ->orderByDesc('ticket_count')
            ->limit(100)
            ->get();
    }

    private function serialHistoryData(string $serialNumber): array
    {
        $tickets = Ticket::query()
            ->where(function ($query) use ($serialNumber): void {
                $query->whereHas('customerMachine', function ($machineQuery) use ($serialNumber): void {
                    $machineQuery->where('serial_number', $serialNumber);
                })->orWhereHas('customerMachineProfile', function ($profileQuery) use ($serialNumber): void {
                    $profileQuery->where('serial_number', $serialNumber);
                });
            })
            ->with(['customerMachine', 'customerMachineProfile'])
            ->orderByDesc('acceptance_date')
            ->orderByDesc('created_at')
            ->get();

        $lastTicket = $tickets->first();

        return [
            'history' => [
                'serial_number' => $serialNumber,
                'count' => $tickets->count(),
                'last_acceptance_date' => $lastTicket?->acceptance_date?->format('d.m.Y'),
                'tickets' => $tickets->map(fn (Ticket $ticket): array => [
                    'ticket_number' => $ticket->ticket_number,
                    'status_label' => $ticket->statusLabel(),
                    'acceptance_date' => $ticket->acceptance_date?->format('d.m.Y'),
                    'created_at' => $ticket->created_at?->format('d.m.Y H:i'),
                    'machine_label' => $ticket->customerMachine?->displayName()
                        ?: trim(($ticket->customerMachineProfile?->manufacturer_snapshot ? $ticket->customerMachineProfile->manufacturer_snapshot.' / ' : '').($ticket->customerMachineProfile?->machine_ref_snapshot ?: '-')),
                    'customer_name' => $ticket->customer_name_snapshot ?: '-',
                    'contact_name' => $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot ?: '-',
                    'url' => route('tickets.show', $ticket),
                ])->all(),
            ],
        ];
    }
}
