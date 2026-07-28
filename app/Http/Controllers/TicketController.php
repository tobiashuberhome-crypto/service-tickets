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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            ->with(['customerMachine', 'customerPortalAccount'])
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

        return view('tickets.index', [
            'weekGroups' => $weekGroups,
            'upcomingWeeks' => $upcomingWeeks,
            'withoutTargetDate' => $withoutTargetDate,
            'schoolPortalIncoming' => $schoolPortalIncoming,
            'easyAppointmentsIncoming' => $easyAppointmentsIncoming,
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

    public function show(Request $request, Ticket $ticket, DolibarrClient $dolibarr): View
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines', 'customerPortalAccount', 'messages.attachments']);

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

    public function generateGeiserInvoice(Request $request, Ticket $ticket, DolibarrClient $dolibarr)
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);
        $invoiceRecipient = $dolibarr->getCustomer((int) $ticket->dolibarr_customer_id);

        $invoiceLines = collect();

        foreach ($ticket->serviceLines as $line) {
            $description = trim((string) $line->label_snapshot);
            $isNmService = strcasecmp(trim((string) $line->product_ref), 'NM-Service') === 0
                || strcasecmp($description, 'NM-Service') === 0;

            $invoiceLines->push([
                'type' => 'Leistung',
                'reference' => $line->product_ref ?: '-',
                'description' => $description !== '' ? $description : 'Serviceleistung',
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->sales_price_snapshot,
                'discount_rate' => $isNmService ? 0.0 : 0.20,
            ]);
        }

        foreach ($ticket->parts as $part) {
            $partLabel = trim((string) $part->label_snapshot);
            $partRef = trim((string) $part->part_ref_snapshot);
            $description = trim($partRef !== '' ? $partRef.' - '.$partLabel : $partLabel);

            $invoiceLines->push([
                'type' => 'Ersatzteil',
                'reference' => $partRef !== '' ? $partRef : '-',
                'description' => $description !== '' ? $description : 'Ersatzteil',
                'quantity' => (float) $part->quantity,
                'unit_price' => (float) $part->sales_price_snapshot,
                'discount_rate' => 0.20,
            ]);
        }

        $invoiceLines = $invoiceLines
            ->map(function (array $line) use ($ticket): array {
                $line['line_total'] = round($line['quantity'] * $line['unit_price'], 2);
                $line['discount_amount'] = round($line['line_total'] * (float) $line['discount_rate'], 2);
                $line['discounted_total'] = round($line['line_total'] - $line['discount_amount'], 2);
                $line['copy_text'] = $this->buildGeiserInvoiceCopyText($ticket, $line['description']);

                return $line;
            })
            ->values();

        $totalOriginalNet = (float) $invoiceLines->sum('line_total');
        $totalDiscountAmount = (float) $invoiceLines->sum('discount_amount');
        $totalNet = (float) $invoiceLines->sum('discounted_total');
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
            'totalOriginalNet' => $totalOriginalNet,
            'totalDiscountAmount' => $totalDiscountAmount,
            'totalNet' => $totalNet,
            'sender' => $sender,
            'bank' => $bank,
            'footerNote' => $footerNote,
            'createdAt' => now(),
        ];

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

    private function buildGeiserInvoiceCopyText(Ticket $ticket, string $articleDescription): string
    {
        $manufacturer = trim((string) ($ticket->customerMachine?->manufacturer_snapshot ?: $ticket->customerMachineProfile?->manufacturer_snapshot));
        $machineRef = trim((string) ($ticket->customerMachine?->machine_ref_snapshot ?: $ticket->customerMachineProfile?->machine_ref_snapshot));
        $serialNumber = trim((string) ($ticket->customerMachine?->serial_number ?: $ticket->customerMachineProfile?->serial_number));

        $machineLabel = trim(($manufacturer !== '' ? $manufacturer : 'Hersteller unbekannt').($machineRef !== '' ? ' '.$machineRef : ''));
        $serialLabel = $serialNumber !== '' ? $serialNumber : 'Seriennummer unbekannt';
        $descriptionLabel = trim($articleDescription) !== '' ? trim($articleDescription) : 'Position ohne Bezeichnung';

        return $machineLabel.' - '.$serialLabel.' - '.$descriptionLabel;
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
