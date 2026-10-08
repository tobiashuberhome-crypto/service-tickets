<?php

namespace App\Http\Controllers;

use App\Models\CustomerMachine;
use App\Models\CustomerPortalAccount;
use App\Models\DeliveryNote;
use App\Models\MachineDocument;
use App\Models\MonthlyInvoice;
use App\Models\SparePart;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Dolibarr\DolibarrClient;
use App\Services\Tickets\DolibarrOrderSyncService;
use App\Services\Tickets\GeiserInvoiceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));
        $hideReturned = $request->boolean('hide_returned');
        $customerFilter = trim((string) $request->query('customer'));

        $customerOptions = Ticket::query()
            ->selectRaw('dolibarr_customer_id, MAX(customer_name_snapshot) as label')
            ->groupBy('dolibarr_customer_id')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->dolibarr_customer_id === null ? 'none' : (string) $row->dolibarr_customer_id,
                'label' => $row->dolibarr_customer_id === null ? 'Ohne Dolibarr-Kunde' : $row->label,
            ])
            ->sortBy('label')
            ->values();

        $tickets = Ticket::query()
            ->with(['customerMachine', 'customerPortalAccount', 'monthlyInvoices', 'deliveryNotes'])
            ->when(array_key_exists($status, Ticket::statusOptions()), fn ($query) => $query->where('status', $status))
            ->when($hideReturned, fn ($query) => $query->where('machine_returned', false))
            ->when($customerFilter !== '', function ($query) use ($customerFilter): void {
                if ($customerFilter === 'none') {
                    $query->whereNull('dolibarr_customer_id');
                } else {
                    $query->where('dolibarr_customer_id', (int) $customerFilter);
                }
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('ticket_number', 'like', '%'.$search.'%')
                        ->orWhere('dolibarr_order_ref', 'like', '%'.$search.'%')
                        ->orWhere('customer_name_snapshot', 'like', '%'.$search.'%')
                        ->orWhereHas('customerMachine', function ($machineQuery) use ($search): void {
                            $machineQuery->where('machine_ref_snapshot', 'like', '%'.$search.'%')
                                ->orWhere('manufacturer_snapshot', 'like', '%'.$search.'%')
                                ->orWhere('serial_number', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByRaw('target_date is null')
            ->orderBy('target_date')
            ->orderBy('target_sort_order')
            ->orderBy('created_at')
            ->get();

        // Eingangs-Kacheln: nur unterminierte Tickets aus Schul-Portal bzw. EasyAppointments
        $schoolPortalIncoming = $tickets->filter(function (Ticket $ticket): bool {
            return $ticket->target_date === null
                && $ticket->customerPortalAccount?->portal_scope === \App\Models\CustomerPortalAccount::PORTAL_SCOPE_SCHOOL;
        });

        $easyAppointmentsIncoming = $tickets->filter(function (Ticket $ticket): bool {
            return $ticket->target_date === null
                && str_contains((string) $ticket->error_description, 'EasyAppointments Termin-ID:');
        });

        $incomingIds = $schoolPortalIncoming
            ->pluck('id')
            ->merge($easyAppointmentsIncoming->pluck('id'))
            ->unique();

        $planningTickets = $tickets->reject(fn (Ticket $ticket): bool => $incomingIds->contains($ticket->id));

        $weekGroups = $planningTickets
            ->filter(fn (Ticket $ticket): bool => $ticket->target_date !== null)
            ->groupBy(fn (Ticket $ticket): string => $ticket->target_date->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
            ->map(function ($weekTickets, string $weekStart): array {
                $start = Carbon::parse($weekStart);
                $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

                return [
                    'key' => $weekStart,
                    'label' => 'KW '.$start->isoWeek().' / '.$start->format('d.m.').' - '.$end->format('d.m.Y'),
                    'tickets' => $weekTickets,
                ];
            })
            ->sortKeys();

        $withoutTargetDate = $planningTickets->filter(fn (Ticket $ticket): bool => $ticket->target_date === null);

        $upcomingWeeks = collect(range(0, 2))->map(function (int $offset): array {
            $start = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeeks($offset);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            return [
                'key' => $start->toDateString(),
                'label' => 'KW '.$start->isoWeek().' / '.$start->format('d.m.').' - '.$end->format('d.m.Y'),
            ];
        });

        $monthGroups = $tickets
            ->groupBy(function (Ticket $ticket): string {
                $date = $ticket->acceptance_date ?? $ticket->created_at;

                return $date ? $date->copy()->startOfMonth()->toDateString() : Carbon::now()->startOfMonth()->toDateString();
            })
            ->map(function ($monthTickets, string $monthKey): array {
                $monthStart = Carbon::parse($monthKey)->startOfMonth();

                return [
                    'key' => $monthKey,
                    'label' => $monthStart->locale('de')->translatedFormat('F Y'),
                    'tickets' => $monthTickets->sortBy(fn (Ticket $ticket) => $ticket->acceptance_date ?? $ticket->created_at),
                ];
            })
            ->sortKeysDesc();

        return view('tickets.index', [
            'weekGroups' => $weekGroups,
            'upcomingWeeks' => $upcomingWeeks,
            'withoutTargetDate' => $withoutTargetDate,
            'schoolPortalIncoming' => $schoolPortalIncoming,
            'easyAppointmentsIncoming' => $easyAppointmentsIncoming,
            'monthGroups' => $monthGroups,
            'statuses' => Ticket::statusOptions(),
            'activeStatus' => $status,
            'search' => $search,
            'hideReturned' => $hideReturned,
            'customerOptions' => $customerOptions,
            'activeCustomer' => $customerFilter,
        ]);
    }


    public function create(): View
    {
        return view('tickets.create', [
            'ticket' => new Ticket([
                'acceptance_date' => now()->toDateString(),
                'status' => Ticket::STATUS_OPEN,
            ]),
            'statuses' => Ticket::statusOptions(),
        ]);
    }

    public function store(Request $request, DolibarrOrderSyncService $sync): RedirectResponse
    {
        $data = $this->validatedTicketData($request);
        $machine = $this->findOrCreateCustomerMachine($data);

        $ticket = Ticket::query()->create([
            'dolibarr_customer_id' => $data['dolibarr_customer_id'],
            'customer_name_snapshot' => $data['customer_name_snapshot'],
            'customer_machine_id' => $machine->id,
            'service_enabled' => $request->boolean('service_enabled'),
            'cleaning' => $request->boolean('cleaning'),
            'repair_enabled' => $request->boolean('repair_enabled'),
            'spare_part_order_required' => $request->boolean('spare_part_order_required'),
            'thss' => $request->boolean('thss'),
            'priority' => $request->boolean('priority'),
            'error_description' => $data['error_description'] ?? null,
            'technician_note' => $data['technician_note'] ?? null,
            'acceptance_date' => $data['acceptance_date'],
            'target_date' => $data['target_date'] ?? null,
            'status' => $data['status'] ?? Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
        ]);

        try {
            $sync->ensureDraftOrder($ticket);
            $sync->prepareServiceLines($ticket);
            if ($ticket->status === Ticket::STATUS_IN_PROGRESS) {
                $sync->activateOrder($ticket);
            } elseif ($ticket->status === Ticket::STATUS_INTERNALLY_DONE) {
                $sync->closeOrderAndCreateInvoice($ticket);
            } elseif ($ticket->status === Ticket::STATUS_DONE) {
                $sync->activateInvoice($ticket);
            }
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('warning', 'Ticket wurde lokal gespeichert, aber Dolibarr konnte nicht synchronisiert werden: '.$exception->getMessage());
        }

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket gespeichert.');
    }

    public function show(Request $request, Ticket $ticket, DolibarrClient $dolibarr, GeiserInvoiceCalculator $invoiceCalculator): View
    {
        $ticket->load(['messages.attachments']);
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines', 'customerPortalAccount', 'messages.attachments', 'monthlyInvoices', 'deliveryNotes']);

        $partsMode = $request->query('parts');
        $partSearch = trim((string) $request->query('part_search'));
        $partCategory = $request->has('part_category') && $request->query('part_category') !== ''
            ? (int) $request->query('part_category')
            : null;
        $partManufacturer = $request->has('part_manufacturer')
            ? trim((string) $request->query('part_manufacturer'))
            : (string) ($ticket->customerMachine?->manufacturer_snapshot ?? $ticket->customerMachineProfile?->manufacturer_snapshot);
        $partMachineRef = $request->has('part_machine_ref')
            ? trim((string) $request->query('part_machine_ref'))
            : (string) ($ticket->customerMachine?->machine_ref_snapshot ?? $ticket->customerMachineProfile?->machine_ref_snapshot);
        $partsWarning = null;

        $availableParts = collect();
        if ($partsMode === 'all' || $partSearch !== '') {
            $machineIds = [];

            if ($partManufacturer !== '' || $partMachineRef !== '') {
                try {
                    $machineIds = collect($dolibarr->searchMachineProducts($partManufacturer, $partMachineRef, 500))
                        ->pluck('id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                } catch (Throwable $exception) {
                    $partsWarning = 'Dolibarr-Maschinenfilter konnte nicht geladen werden: '.$exception->getMessage();
                }
            }

            $availableParts = SparePart::query()
                ->active()
                ->when($partCategory !== null, function ($query) use ($partCategory): void {
                    $query->where('category_id', $partCategory);
                })
                ->when($partManufacturer !== '', function ($query) use ($partManufacturer): void {
                    $query->where('manufacturer', 'like', '%'.$partManufacturer.'%');
                })
                ->when($partMachineRef !== '', function ($query) use ($machineIds, $partMachineRef): void {
                    $query->where(function ($machineQuery) use ($machineIds, $partMachineRef): void {
                        // First try ref-based compatibility (preferred - much more stable than ID-based)
                        if ($partMachineRef !== '') {
                            $machineQuery->whereHas('compatibilities', function ($compatibility) use ($partMachineRef): void {
                                $compatibility->where('machine_ref', $partMachineRef);
                            });
                        }
                        
                        // Also check ID-based compatibility for backwards compatibility with existing data
                        if ($machineIds !== []) {
                            $machineQuery->{$partMachineRef !== '' ? 'orWhereHas' : 'whereHas'}('compatibilities', function ($compatibility) use ($machineIds): void {
                                $compatibility->whereIn('machine_product_id', $machineIds);
                            });
                        }
                        
                        // Always keep a text-based fallback for resilience when compatibility data is incomplete
                        $machineQuery->{($partMachineRef !== '' || $machineIds !== []) ? 'orWhere' : 'where'}(function ($textQuery) use ($partMachineRef): void {
                            $textQuery->where('part_ref', 'like', '%'.$partMachineRef.'%')
                                ->orWhere('label', 'like', '%'.$partMachineRef.'%')
                                ->orWhere('manufacturer', 'like', '%'.$partMachineRef.'%');
                        });
                    });
                })
                ->search($partSearch)
                ->when($partSearch !== '', function ($query) use ($partSearch): void {
                    // Prioritize label matches before part_ref matches
                    $like = '%'.$partSearch.'%';
                    $query->orderByRaw("(label LIKE ?) DESC, (part_ref LIKE ?) DESC", [$like, $like]);
                })
                ->orderBy('part_ref')
                ->limit($partsMode === 'all' ? 300 : 50)
                ->get();
        }

        // Tickets ohne verknuepfte Maschine (nur Maschinenprofil) haben evtl. keine Referenz -
        // dann gibt es keine passenden Dokumente, statt eines Treffers auf leere Werte.
        $documentMachineRef = $ticket->customerMachine?->machine_ref_snapshot ?? $ticket->customerMachineProfile?->machine_ref_snapshot;
        $documentProductId = $ticket->customerMachine?->dolibarr_machine_product_id;

        $documents = MachineDocument::query()
            ->where('active', true)
            ->where(function ($query) use ($documentMachineRef, $documentProductId): void {
                if (blank($documentMachineRef) && blank($documentProductId)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->when(filled($documentMachineRef), fn ($q) => $q->where('machine_ref', $documentMachineRef))
                    ->when(filled($documentProductId), fn ($q) => $q->orWhere('machine_product_id', $documentProductId));
            })
            ->orderBy('title')
            ->get();

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => Ticket::statusOptions(),
            'availableParts' => $availableParts,
            'partSearch' => $partSearch,
            'partsMode' => $partsMode,
            'partManufacturer' => $partManufacturer,
            'partMachineRef' => $partMachineRef,
            'partsWarning' => $partsWarning,
            'documents' => $documents,
            'invoiceSummary' => $invoiceCalculator->summarize($ticket),
        ]);
    }

    public function update(Request $request, Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        if ($ticket->isDone()) {
            $data = $request->validate([
                'status' => ['required', 'in:'.implode(',', array_keys(Ticket::statusOptions()))],
            ]);

            $isNowDone = in_array($data['status'], [Ticket::STATUS_DONE, Ticket::STATUS_DELIVERED], true);

            $ticket->forceFill([
                'status' => $data['status'],
                'completed_at' => $isNowDone ? ($ticket->completed_at ?? now()) : null,
                'machine_returned' => $request->boolean('machine_returned'),
                'thss' => $request->boolean('thss'),
                'priority' => $request->boolean('priority'),
                'sync_status' => Ticket::SYNC_PENDING,
                'sync_message' => null,
            ])->save();

            return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket-Status aktualisiert.');
        }

        $data = $this->validatedTicketData($request);
        $machine = $this->findOrCreateCustomerMachine($data);

        $ticket->fill([
            'dolibarr_customer_id' => $data['dolibarr_customer_id'],
            'customer_name_snapshot' => $data['customer_name_snapshot'],
            'customer_machine_id' => $machine->id,
            'service_enabled' => $request->boolean('service_enabled'),
            'cleaning' => $request->boolean('cleaning'),
            'repair_enabled' => $request->boolean('repair_enabled'),
            'spare_part_order_required' => $request->boolean('spare_part_order_required'),
            'machine_returned' => $request->boolean('machine_returned'),
            'thss' => $request->boolean('thss'),
            'priority' => $request->boolean('priority'),
            'error_description' => $data['error_description'] ?? null,
            'technician_note' => $data['technician_note'] ?? null,
            'acceptance_date' => $data['acceptance_date'],
            'target_date' => $data['target_date'] ?? null,
            'status' => $data['status'] ?? Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
            'sync_message' => null,
        ])->save();

        try {
            $sync->ensureDraftOrder($ticket);
            $sync->prepareServiceLines($ticket);
            if ($ticket->status === Ticket::STATUS_IN_PROGRESS) {
                $sync->activateOrder($ticket);
            } elseif ($ticket->status === Ticket::STATUS_INTERNALLY_DONE) {
                $sync->closeOrderAndCreateInvoice($ticket);
            } elseif ($ticket->status === Ticket::STATUS_DONE) {
                $sync->activateInvoice($ticket);
            }
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());

            return back()->with('warning', 'Gespeichert, aber Dolibarr-Sync fehlgeschlagen: '.$exception->getMessage());
        }

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket gespeichert.');
    }


    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'week_start' => ['nullable', 'date'],
            'ticket_ids' => ['required', 'array'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
        ]);

        $weekStart = isset($data['week_start']) && $data['week_start']
            ? Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)
            : null;
        $targetWeekEnd = $weekStart?->copy()->endOfWeek(Carbon::SUNDAY);

        DB::transaction(function () use ($data, $weekStart, $targetWeekEnd): void {
            foreach (array_values($data['ticket_ids']) as $index => $ticketId) {
                $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticketId);
                $targetDate = $ticket->target_date;

                if ($weekStart === null) {
                    $targetDate = null;
                } elseif (! $targetDate || $targetDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString() !== $weekStart->toDateString()) {
                    $targetDate = $targetWeekEnd;
                }

                $ticket->forceFill([
                    'target_date' => $targetDate,
                    'target_sort_order' => $index + 1,
                ])->save();
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function complete(Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        if ($ticket->isDone()) {
            return back()->with('status', 'Ticket ist bereits erledigt.');
        }

        try {
            $sync->complete($ticket);
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());

            return back()->with('warning', 'Dolibarr-Sync fehlgeschlagen: '.$exception->getMessage());
        }

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket erledigt und an Dolibarr uebertragen.');
    }

    public function retrySync(Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        try {
            $sync->ensureDraftOrder($ticket);
            $sync->prepareServiceLines($ticket);
            if ($ticket->status === Ticket::STATUS_IN_PROGRESS) {
                $sync->activateOrder($ticket);
            } elseif ($ticket->status === Ticket::STATUS_INTERNALLY_DONE) {
                $sync->closeOrderAndCreateInvoice($ticket);
            } elseif ($ticket->status === Ticket::STATUS_DONE) {
                $sync->activateInvoice($ticket);
            }
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());

            return back()->with('warning', 'Dolibarr-Sync weiterhin fehlgeschlagen: '.$exception->getMessage());
        }

        return back()->with('status', 'Dolibarr-Sync erfolgreich.');
    }

    public function activateOrder(Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        try {
            $sync->activateOrder($ticket);
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());
            return back()->with('warning', 'Auftrag konnte nicht aktiviert werden: '.$exception->getMessage());
        }
        return redirect()->route('tickets.show', $ticket)->with('status', 'Auftrag in Dolibarr aktiviert.');
    }

    public function closeOrderAndCreateInvoice(Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        try {
            $sync->closeOrderAndCreateInvoice($ticket);
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());
            return back()->with('warning', 'Auftrag schlieÃŸen / Rechnung anlegen fehlgeschlagen: '.$exception->getMessage());
        }
        return redirect()->route('tickets.show', $ticket)->with('status', 'Auftrag erledigt und Rechnung angelegt.');
    }

    public function activateInvoice(Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        try {
            $sync->activateInvoice($ticket);
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());
            return back()->with('warning', 'Rechnung konnte nicht aktiviert werden: '.$exception->getMessage());
        }
        return redirect()->route('tickets.show', $ticket)->with('status', 'Rechnung in Dolibarr aktiviert.');
    }

    public function generateDeliveryNote(Request $request, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $data = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
        ]);

        $tickets = Ticket::query()
            ->with(['customerMachine', 'parts', 'serviceLines'])
            ->whereIn('id', $data['ticket_ids'])
            ->orderBy('ticket_number')
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('warning', 'Es wurden keine Tickets fÃ¼r den Lieferschein ausgewÃ¤hlt.');
        }

        Ticket::query()
            ->whereIn('id', $tickets->pluck('id')->all())
            ->update(['status' => Ticket::STATUS_DELIVERED]);

        $invoiceSummaryByTicket = $tickets
            ->mapWithKeys(fn (Ticket $ticket): array => [(string) $ticket->id => $invoiceCalculator->summarize($ticket)])
            ->all();
        $deliveryTotalGross = round(
            (float) collect($invoiceSummaryByTicket)->sum(fn (array $summary): float => (float) ($summary['totalGross'] ?? 0)),
            2
        );

        $fileName = 'lieferschein-'.now()->format('Ymd-His').'.pdf';
        $payload = [
            'tickets' => $tickets,
            'createdAt' => now(),
            'invoiceSummaryByTicket' => $invoiceSummaryByTicket,
            'deliveryTotalGross' => $deliveryTotalGross,
        ];

        $deliveryNote = DeliveryNote::query()->create([
            'filename' => $fileName,
            'disk' => 'local',
            'path' => 'delivery-notes/'.$fileName,
            'created_by' => $request->session()->get('admin_user_id'),
        ]);
        $deliveryNote->tickets()->attach($tickets->pluck('id')->all());

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.delivery-note', $payload);
        }

        $pdfBinary = Pdf::loadView('tickets.delivery-note', $payload)->setPaper('a4', 'portrait')->output();
        Storage::disk('local')->put($deliveryNote->path, $pdfBinary);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Lieferschein-Kopie mit TH Services & Solutions als Absender (Kleinunternehmer gem. §19 UStG).
     * Summen sind Netto = Brutto, siehe generateThServicesInvoice().
     */
    public function generateThServicesDeliveryNote(Request $request, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $data = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
        ]);

        $tickets = Ticket::query()
            ->with(['customerMachine', 'parts', 'serviceLines'])
            ->whereIn('id', $data['ticket_ids'])
            ->orderBy('ticket_number')
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('warning', 'Es wurden keine Tickets für den Lieferschein ausgewählt.');
        }

        Ticket::query()
            ->whereIn('id', $tickets->pluck('id')->all())
            ->update(['status' => Ticket::STATUS_DELIVERED]);

        $invoiceSummaryByTicket = $tickets
            ->mapWithKeys(fn (Ticket $ticket): array => [(string) $ticket->id => $invoiceCalculator->summarize($ticket)])
            ->all();
        $deliveryTotalNet = round(
            (float) collect($invoiceSummaryByTicket)->sum(fn (array $summary): float => (float) ($summary['totalNet'] ?? 0)),
            2
        );

        $fileName = 'th-services-lieferschein-'.now()->format('Ymd-His').'.pdf';
        $payload = [
            'tickets' => $tickets,
            'createdAt' => now(),
            'invoiceSummaryByTicket' => $invoiceSummaryByTicket,
            'deliveryTotalNet' => $deliveryTotalNet,
            'sender' => config('th_services_invoice.sender', []),
            'vatNote' => (string) config('th_services_invoice.vat_note', ''),
        ];

        $deliveryNote = DeliveryNote::query()->create([
            'filename' => $fileName,
            'disk' => 'local',
            'path' => 'delivery-notes/'.$fileName,
            'created_by' => $request->session()->get('admin_user_id'),
        ]);
        $deliveryNote->tickets()->attach($tickets->pluck('id')->all());

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.th-services-delivery-note', $payload);
        }

        $pdfBinary = Pdf::loadView('tickets.th-services-delivery-note', $payload)->setPaper('a4', 'portrait')->output();
        Storage::disk('local')->put($deliveryNote->path, $pdfBinary);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function generateMonthlyInvoice(Request $request, DolibarrClient $dolibarr, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $data = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
        ]);

        $tickets = Ticket::query()
            ->with(['customerMachine', 'customerMachineProfile', 'customerPortalAccount', 'parts', 'serviceLines'])
            ->whereIn('id', $data['ticket_ids'])
            ->orderBy('acceptance_date')
            ->orderBy('ticket_number')
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('warning', 'Es wurden keine Tickets für die Monatsrechnung ausgewählt.');
        }

        if ($tickets->contains(fn (Ticket $ticket): bool => $ticket->thss)) {
            return back()->with('warning', 'Die Auswahl enthält Tickets mit dem Kennzeichen THSS. Diese gehören nicht in die reguläre Monatsrechnung - bitte "Monatsrechnung erstellen (THSS)" verwenden.');
        }

        if ($tickets->pluck('dolibarr_customer_id')->unique()->count() > 1) {
            return back()->with('warning', 'Eine Monatsrechnung kann nur Tickets desselben Kunden enthalten. Bitte Auswahl auf einen Kunden eingrenzen.');
        }

        try {
            $invoiceRecipient = $dolibarr->getCustomer((int) $tickets->first()->dolibarr_customer_id);
        } catch (Throwable $exception) {
            Log::warning('Monatsrechnung: Dolibarr-Kunde konnte nicht geladen werden, verwende Ticket-Snapshot.', [
                'dolibarr_customer_id' => $tickets->first()->dolibarr_customer_id,
                'error' => $exception->getMessage(),
            ]);
            $invoiceRecipient = ['name' => $tickets->first()->customer_name_snapshot];
        }

        $invoiceSummary = $invoiceCalculator->summarizeMany($tickets);

        $hoursLines = collect($invoiceSummary['invoiceLines'])->where('is_nm_service', true)->values();
        $monthlyTotalHours = round((float) $hoursLines->sum('quantity'), 2);

        $monthDate = $tickets->first()->acceptance_date ?? $tickets->first()->created_at ?? now();
        $monthLabel = $monthDate->copy()->locale('de')->translatedFormat('F Y');

        $portalScopes = $tickets->pluck('customerPortalAccount.portal_scope')->filter()->unique();
        $portalScope = $portalScopes->count() === 1 ? $portalScopes->first() : CustomerPortalAccount::PORTAL_SCOPE_DEFAULT;

        $sequenceNumber = 1 + (int) (MonthlyInvoice::query()
            ->where('portal_scope', $portalScope)
            ->where('dolibarr_customer_id', $tickets->first()->dolibarr_customer_id)
            ->where('invoice_year', (int) $monthDate->format('Y'))
            ->where('invoice_month', (int) $monthDate->format('m'))
            ->max('sequence_number') ?? 0);

        $monthlyInvoice = MonthlyInvoice::query()->create([
            'portal_scope' => $portalScope,
            'dolibarr_customer_id' => $tickets->first()->dolibarr_customer_id,
            'invoice_year' => (int) $monthDate->format('Y'),
            'invoice_month' => (int) $monthDate->format('m'),
            'sequence_number' => $sequenceNumber,
            'generated_at' => now(),
        ]);
        $monthlyInvoice->tickets()->attach($tickets->pluck('id')->all());

        $fileName = 'monatsrechnung-'.$monthDate->copy()->format('Y-m').'.pdf';
        $hoursFileName = 'stundennachweis-'.$monthDate->copy()->format('Y-m').'.pdf';
        $invoiceNumber = 'MON-'.$monthDate->copy()->format('Ym').'-'.$tickets->first()->dolibarr_customer_id;

        $letterhead = [
            'sender' => config('geiser_invoice.sender', []),
            'bank' => config('geiser_invoice.bank', []),
            'footerNote' => (string) config('geiser_invoice.footer_note', ''),
            'invoiceRecipient' => $invoiceRecipient,
            'invoiceNumber' => $invoiceNumber,
        ];

        $payload = array_merge($letterhead, $invoiceSummary, [
            'tickets' => $tickets,
            'createdAt' => now(),
            'monthLabel' => $monthLabel,
        ]);

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.monthly-invoice', $payload);
        }

        $invoicePdfBinary = Pdf::loadView('tickets.monthly-invoice', $payload)->setPaper('a4', 'portrait')->output();

        if (! class_exists(\ZipArchive::class)) {
            return response($invoicePdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
        }

        $hoursPayload = array_merge($letterhead, [
            'tickets' => $tickets,
            'createdAt' => now(),
            'hoursLines' => $hoursLines,
            'monthLabel' => $monthLabel,
            'monthlyTotalHours' => $monthlyTotalHours,
        ]);
        $hoursPdfBinary = Pdf::loadView('tickets.monthly-invoice-hours', $hoursPayload)->setPaper('a4', 'portrait')->output();

        $zipPath = tempnam(sys_get_temp_dir(), 'monatsrechnung_').'.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($fileName, $invoicePdfBinary);
        $zip->addFromString($hoursFileName, $hoursPdfBinary);
        $zip->close();

        $zipFileName = 'monatsrechnung-'.$monthDate->copy()->format('Y-m').'.zip';

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Monatsrechnung (inkl. Stundennachweis) mit TH Services & Solutions als Rechnungssteller
     * (Kleinunternehmer gem. §19 UStG, Netto = Brutto). Nur im Admin verfuegbar und ausschliesslich
     * fuer Tickets mit Kennzeichen THSS; diese Tickets erscheinen in keinem externen Portal.
     */
    public function generateThServicesMonthlyInvoice(Request $request, DolibarrClient $dolibarr, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $data = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
        ]);

        $tickets = Ticket::query()
            ->with(['customerMachine', 'customerMachineProfile', 'customerPortalAccount', 'parts', 'serviceLines'])
            ->whereIn('id', $data['ticket_ids'])
            ->orderBy('acceptance_date')
            ->orderBy('ticket_number')
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('warning', 'Es wurden keine Tickets für die Monatsrechnung THSS ausgewählt.');
        }

        if ($tickets->contains(fn (Ticket $ticket): bool => ! $ticket->thss)) {
            return back()->with('warning', 'Die Monatsrechnung THSS kann nur Tickets mit dem Kennzeichen THSS enthalten. Bitte Auswahl anpassen.');
        }

        if ($tickets->pluck('dolibarr_customer_id')->unique()->count() > 1) {
            return back()->with('warning', 'Eine Monatsrechnung kann nur Tickets desselben Kunden enthalten. Bitte Auswahl auf einen Kunden eingrenzen.');
        }

        $customerId = (int) $tickets->first()->dolibarr_customer_id;

        try {
            $invoiceRecipient = $dolibarr->getCustomer($customerId);
        } catch (Throwable $exception) {
            Log::warning('Monatsrechnung THSS: Dolibarr-Kunde konnte nicht geladen werden, verwende Ticket-Snapshot.', [
                'dolibarr_customer_id' => $customerId,
                'error' => $exception->getMessage(),
            ]);
            $invoiceRecipient = ['name' => $tickets->first()->customer_name_snapshot];
        }

        $invoiceSummary = $invoiceCalculator->summarizeMany($tickets);
        $invoiceLines = collect($invoiceSummary['invoiceLines']);

        // Kleinunternehmer: Netto = Brutto, je Ticket die Summe der Positionen nach Rabatt.
        $thTicketTotals = collect($invoiceSummary['ticketTotals'])
            ->map(function (array $ticketTotal) use ($invoiceLines): array {
                $ticketTotal['net_total'] = round(
                    (float) $invoiceLines->where('ticket_number', $ticketTotal['ticket_number'])->sum('line_net_after_discount'),
                    2
                );

                return $ticketTotal;
            })
            ->values();

        $hoursLines = $invoiceLines->where('is_nm_service', true)->values();
        $monthlyTotalHours = round((float) $hoursLines->sum('quantity'), 2);

        $monthDate = $tickets->first()->acceptance_date ?? $tickets->first()->created_at ?? now();
        $monthLabel = $monthDate->copy()->locale('de')->translatedFormat('F Y');

        $sequenceNumber = 1 + (int) (MonthlyInvoice::query()
            ->where('portal_scope', MonthlyInvoice::SCOPE_TH_SERVICES)
            ->where('dolibarr_customer_id', $customerId)
            ->where('invoice_year', (int) $monthDate->format('Y'))
            ->where('invoice_month', (int) $monthDate->format('m'))
            ->max('sequence_number') ?? 0);

        $monthlyInvoice = MonthlyInvoice::query()->create([
            'portal_scope' => MonthlyInvoice::SCOPE_TH_SERVICES,
            'dolibarr_customer_id' => $customerId,
            'invoice_year' => (int) $monthDate->format('Y'),
            'invoice_month' => (int) $monthDate->format('m'),
            'sequence_number' => $sequenceNumber,
            'generated_at' => now(),
        ]);
        $monthlyInvoice->tickets()->attach($tickets->pluck('id')->all());

        $suffix = $monthDate->copy()->format('Y-m').'-'.$sequenceNumber;
        $fileName = 'th-services-monatsrechnung-'.$suffix.'.pdf';
        $hoursFileName = 'th-services-stundennachweis-'.$suffix.'.pdf';
        $invoiceNumber = 'THS-MON-'.$monthDate->copy()->format('Ym').'-'.$customerId.'-'.$sequenceNumber;

        $letterhead = [
            'sender' => config('th_services_invoice.sender', []),
            'bank' => config('th_services_invoice.bank', []),
            'vatNote' => (string) config('th_services_invoice.vat_note', ''),
            'invoiceRecipient' => $invoiceRecipient,
            'invoiceNumber' => $invoiceNumber,
        ];

        $payload = array_merge($letterhead, [
            'tickets' => $tickets,
            'createdAt' => now(),
            'monthLabel' => $monthLabel,
            'thTicketTotals' => $thTicketTotals,
            'totalNet' => round((float) $thTicketTotals->sum('net_total'), 2),
        ]);

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.th-services-monthly-invoice', $payload);
        }

        $invoicePdfBinary = Pdf::loadView('tickets.th-services-monthly-invoice', $payload)->setPaper('a4', 'portrait')->output();

        if (! class_exists(\ZipArchive::class)) {
            return response($invoicePdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
        }

        $hoursPayload = array_merge($letterhead, [
            'tickets' => $tickets,
            'createdAt' => now(),
            'hoursLines' => $hoursLines,
            'monthLabel' => $monthLabel,
            'monthlyTotalHours' => $monthlyTotalHours,
        ]);
        $hoursPdfBinary = Pdf::loadView('tickets.th-services-monthly-invoice-hours', $hoursPayload)->setPaper('a4', 'portrait')->output();

        $zipPath = tempnam(sys_get_temp_dir(), 'th_monatsrechnung_').'.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($fileName, $invoicePdfBinary);
        $zip->addFromString($hoursFileName, $hoursPdfBinary);
        $zip->close();

        return response()->download($zipPath, 'th-services-monatsrechnung-'.$suffix.'.zip')->deleteFileAfterSend(true);
    }

    public function printTicket(Ticket $ticket, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);

        $payload = [
            'ticket' => $ticket,
            'invoiceSummary' => $invoiceCalculator->summarize($ticket),
            'generatedAt' => now(),
        ];

        $fileName = 'ticket-'.$ticket->ticket_number.'.pdf';

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.print', $payload);
        }

        $pdf = Pdf::loadView('tickets.print', $payload)->setPaper('a4', 'portrait');

        return $pdf->stream($fileName);
    }

    public function generateGeiserInvoice(Request $request, Ticket $ticket, DolibarrClient $dolibarr, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);
        $invoiceRecipient = $dolibarr->getCustomer((int) $ticket->dolibarr_customer_id);
        $invoiceSummary = $invoiceCalculator->summarize($ticket);
        $invoiceLines = $invoiceCalculator->withCopyTexts($ticket, $invoiceSummary['invoiceLines']);
        $sender = config('geiser_invoice.sender', []);
        $bank = config('geiser_invoice.bank', []);
        $footerNote = (string) config('geiser_invoice.footer_note', '');
        if (isset($bank['payment_note']) && is_string($bank['payment_note'])) {
            $bank['payment_note'] = str_replace('{ticket}', $ticket->ticket_number, $bank['payment_note']);
        }
        if ($footerNote !== '') {
            $footerNote = str_replace('{ticket}', $ticket->ticket_number, $footerNote);
        }
        $fileName = 'il-coccolino-rechnung-'.$ticket->ticket_number.'.pdf';

        $payload = [
            'ticket' => $ticket,
            'invoiceRecipient' => $invoiceRecipient,
            'invoiceLines' => $invoiceLines,
            'sender' => $sender,
            'bank' => $bank,
            'footerNote' => $footerNote,
            'createdAt' => now(),
        ];
        $payload = array_merge($payload, $invoiceSummary);
        $payload['invoiceLines'] = $invoiceLines;

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.geiser-invoice', $payload);
        }

        $pdf = Pdf::loadView('tickets.geiser-invoice', $payload)->setPaper('a4', 'portrait');
        $pdfBinary = $pdf->output();
        if ($request->boolean('send_mail')) {
            $this->sendGeiserInvoiceMail($ticket, $fileName, $pdfBinary);
        }

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Content-Length' => (string) strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Alternative Rechnungskopie mit TH Services & Solutions als Rechnungssteller (Kleinunternehmer
     * gem. §19 UStG). Der Rechnungsbetrag entspricht dem Netto-Betrag der regulaeren Rechnung, da
     * hier keine Umsatzsteuer ausgewiesen wird - Netto = Brutto.
     */
    public function generateThServicesInvoice(Ticket $ticket, DolibarrClient $dolibarr, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);
        $invoiceRecipient = $dolibarr->getCustomer((int) $ticket->dolibarr_customer_id);
        $invoiceSummary = $invoiceCalculator->summarize($ticket);

        $payload = [
            'ticket' => $ticket,
            'invoiceRecipient' => $invoiceRecipient,
            'invoiceLines' => $invoiceSummary['invoiceLines'],
            'totalNet' => $invoiceSummary['totalNet'],
            'sender' => config('th_services_invoice.sender', []),
            'bank' => config('th_services_invoice.bank', []),
            'vatNote' => (string) config('th_services_invoice.vat_note', ''),
            'createdAt' => now(),
        ];

        $fileName = 'th-services-rechnung-'.$ticket->ticket_number.'.pdf';

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.th-services-invoice', $payload);
        }

        $pdf = Pdf::loadView('tickets.th-services-invoice', $payload)->setPaper('a4', 'portrait');

        return $pdf->stream($fileName);
    }

    private function validatedTicketData(Request $request): array
    {
        $data = $request->validate([
            'dolibarr_customer_id' => ['required', 'integer'],
            'customer_name_snapshot' => ['required', 'string', 'max:255'],
            'dolibarr_machine_product_id' => ['required', 'integer'],
            'manufacturer_snapshot' => ['nullable', 'string', 'max:255'],
            'machine_ref_snapshot' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'service_enabled' => ['nullable', 'boolean'],
            'cleaning' => ['nullable', 'boolean'],
            'repair_enabled' => ['nullable', 'boolean'],
            'spare_part_order_required' => ['nullable', 'boolean'],
            'error_description' => ['nullable', 'string'],
            'technician_note' => ['nullable', 'string'],
            'acceptance_date' => ['required', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:acceptance_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Ticket::statusOptions()))],
        ]);

        if ($request->boolean('repair_enabled') && blank($data['error_description'] ?? null)) {
            throw ValidationException::withMessages([
                'error_description' => 'Bitte eine Fehlerbeschreibung eintragen, wenn Reparatur aktiviert ist.',
            ]);
        }

        return $data;
    }

    private function sendGeiserInvoiceMail(Ticket $ticket, string $fileName, string $pdfBinary): void
    {
        $mailConfig = config('geiser_invoice.mail', []);
        $recipients = collect(explode(',', (string) ($mailConfig['to'] ?? '')))
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => $email !== '')
            ->values()
            ->all();

        if ($recipients === []) {
            return;
        }

        $serialNumbers = collect([
            trim((string) ($ticket->customerMachine?->serial_number ?? '')),
            trim((string) ($ticket->customerMachineProfile?->serial_number ?? '')),
        ])->filter()->unique()->values()->all();

        $serialsText = $serialNumbers !== [] ? implode(' / ', $serialNumbers) : 'Seriennummer unbekannt';
        $invoiceNumber = 'ILC-'.$ticket->ticket_number;
        $replacements = [
            '{ticket}' => $ticket->ticket_number,
            '{serials}' => $serialsText,
            '{customer}' => (string) ($ticket->customer_name_snapshot ?: '-'),
            '{invoice_number}' => $invoiceNumber,
        ];

        $subjectTemplate = (string) ($mailConfig['subject'] ?? 'Rechnung - {serials}');
        $bodyTemplate = (string) ($mailConfig['body'] ?? '');
        $subject = strtr($subjectTemplate, $replacements);
        $body = strtr($bodyTemplate, $replacements);
        $fromAddress = (string) ($mailConfig['from_address'] ?? 'service@example.com');
        $fromName = (string) ($mailConfig['from_name'] ?? 'Service Tickets');

        try {
            Mail::raw($body, function ($message) use ($recipients, $subject, $fromAddress, $fromName, $pdfBinary, $fileName): void {
                $message->to($recipients)
                    ->from($fromAddress, $fromName)
                    ->subject($subject)
                    ->attachData($pdfBinary, $fileName, ['mime' => 'application/pdf']);
            });
        } catch (Throwable $exception) {
            Log::warning('Geiser-Rechnung konnte nicht per E-Mail versendet werden.', [
                'ticket_id' => $ticket->id,
                'recipients' => $recipients,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function findOrCreateCustomerMachine(array $data): CustomerMachine
    {
        $machine = CustomerMachine::query()->firstOrNew([
            'dolibarr_customer_id' => $data['dolibarr_customer_id'],
            'dolibarr_machine_product_id' => $data['dolibarr_machine_product_id'],
            'serial_number' => $data['serial_number'] ?? null,
        ]);

        $machine->fill([
            'customer_name_snapshot' => $data['customer_name_snapshot'],
            'manufacturer_snapshot' => $data['manufacturer_snapshot'] ?? null,
            'machine_ref_snapshot' => $data['machine_ref_snapshot'],
        ])->save();

        return $machine;
    }
}
