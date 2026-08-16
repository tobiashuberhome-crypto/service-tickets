# Datei: app\Http\Controllers\TicketController.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Controllers\TicketController.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Controllers;

use App\Models\CustomerMachine;
use App\Models\MachineDocument;
use App\Models\SparePart;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Dolibarr\DolibarrClient;
use App\Services\Tickets\DolibarrOrderSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $tickets = Ticket::query()
            ->with('customerMachine')
            ->when(array_key_exists($status, Ticket::statusOptions()), fn ($query) => $query->where('status', $status))
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

        $weekGroups = $tickets
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
            });

        $withoutTargetDate = $tickets->filter(fn (Ticket $ticket): bool => $ticket->target_date === null);

        return view('tickets.index', [
            'weekGroups' => $weekGroups,
            'withoutTargetDate' => $withoutTargetDate,
            'statuses' => Ticket::statusOptions(),
            'activeStatus' => $status,
            'search' => $search,
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
            'error_description' => $data['error_description'] ?? null,
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

    public function show(Request $request, Ticket $ticket, DolibarrClient $dolibarr): View
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);

        $partsMode = $request->query('parts');
        $partSearch = trim((string) $request->query('part_search'));
        $partCategory = $request->has('part_category') && $request->query('part_category') !== ''
            ? (int) $request->query('part_category')
            : null;
        $partManufacturer = $request->has('part_manufacturer')
            ? trim((string) $request->query('part_manufacturer'))
            : (string) $ticket->customerMachine->manufacturer_snapshot;
        $partMachineRef = $request->has('part_machine_ref')
            ? trim((string) $request->query('part_machine_ref'))
            : (string) $ticket->customerMachine->machine_ref_snapshot;
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

        $documents = MachineDocument::query()
            ->where('active', true)
            ->where(function ($query) use ($ticket): void {
                $query->where('machine_ref', $ticket->customerMachine->machine_ref_snapshot)
                    ->orWhere('machine_product_id', $ticket->customerMachine->dolibarr_machine_product_id);
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
        ]);
    }

    public function update(Request $request, Ticket $ticket, DolibarrOrderSyncService $sync): RedirectResponse
    {
        if ($ticket->isDone()) {
            return back()->with('warning', 'Erledigte Tickets koennen nicht mehr bearbeitet werden.');
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
            'error_description' => $data['error_description'] ?? null,
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
        $targetFriday = $weekStart?->copy()->addDays(4);

        DB::transaction(function () use ($data, $weekStart, $targetFriday): void {
            foreach (array_values($data['ticket_ids']) as $index => $ticketId) {
                $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticketId);
                $targetDate = $ticket->target_date;

                if ($weekStart === null) {
                    $targetDate = null;
                } elseif (! $targetDate || $targetDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString() !== $weekStart->toDateString()) {
                    $targetDate = $targetFriday;
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

    public function generateDeliveryNote(Request $request)
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

        $fileName = 'lieferschein-'.now()->format('Ymd-His').'.pdf';
        $payload = [
            'tickets' => $tickets,
            'createdAt' => now(),
        ];

        if (! class_exists(Pdf::class)) {
            return response()->view('tickets.delivery-note', $payload);
        }

        $pdf = Pdf::loadView('tickets.delivery-note', $payload)->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
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

```
