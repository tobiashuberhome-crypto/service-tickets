<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CustomerMachine;
use App\Models\CustomerMachineProfile;
use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalMagicLink;
use App\Models\Ticket;
use App\Services\Ocr\OcrService;
use App\Services\Ocr\OcrDataParser;
use App\Services\Tickets\DolibarrOrderSyncService;
use App\Services\Tickets\GeiserInvoiceCalculator;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class GeiserCustomerPortalController extends Controller
{
    private const CUSTOMER_ID = 9;
    private const SESSION_KEY = 'geiser_customer_portal_account_id';
    private const ESTIMATE_DEFINITIONS = [
        'estimate_qty_tech' => ['label' => 'Arbeitseinheit Technik', 'hint' => '10 Minuten pro Einheit', 'unit_price' => 16.90],
        'estimate_qty_service_fee' => ['label' => 'Servicegebuehr', 'hint' => 'Abwicklung Auftrag sowie Transport in die Werkstatt und zurueck', 'unit_price' => 29.00],
        'estimate_qty_vde' => ['label' => 'VDE-Pruefung', 'hint' => 'Schutzleiter- und Isolationspruefung', 'unit_price' => 6.50],
        'estimate_qty_consumables' => ['label' => 'Verbrauchsmaterialien', 'hint' => 'Nadel, Faden sowie Fette und Oele', 'unit_price' => 4.50],
    ];
    private const ESTIMATE_DEFAULT_QUANTITIES = [
        'estimate_qty_tech' => 9.0,
        'estimate_qty_service_fee' => 1.0,
        'estimate_qty_vde' => 1.0,
        'estimate_qty_consumables' => 1.0,
		'repair_approval_limit' => 200,00,
    ];

    public function home(Request $request): View
    {
        return view('customer-portal-geiser.home', [
            'account' => $this->optionalAccount($request),
        ]);
    }

    public function login(): View
    {
        return view('customer-portal-geiser.login');
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $account = $this->findGeiserAccountByEmail($data['email']);

        if ($account) {
            $plainToken = Str::random(64);
            $magicLink = CustomerPortalMagicLink::query()->create([
                'customer_portal_account_id' => $account->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(30),
            ]);

            $url = route('geiser-portal.magic', ['token' => $plainToken]);

            try {
                Mail::raw("Guten Tag,\n\nueber den folgenden Link koennen Sie sich im Il Coccolino-Serviceportal anmelden. Der Link ist 30 Minuten gueltig und kann nur einmal verwendet werden:\n\n{$url}\n\nFalls Sie diesen Link nicht angefordert haben, koennen Sie diese E-Mail ignorieren.\n", function ($message) use ($account): void {
                    $message->to($account->email)->subject('Ihr Zugang zum Il Coccolino-Serviceportal');
                });
            } catch (Throwable $exception) {
                Log::warning('Magic-Link-Mail fuer Geiser-Portal konnte nicht gesendet werden.', [
                    'account_id' => $account->id,
                    'magic_link_id' => $magicLink->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Falls fuer diese E-Mail ein Il Coccolino-Portalzugang existiert, wurde ein Magic Link versendet.');
    }

    public function loginWithPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $account = $this->findGeiserAccountByEmail($data['email']);

        $password = (string) ($account?->password ?? '');
        $passwordMatches = $account
            && $password !== ''
            && (
                Hash::check($data['password'], $password)
                || hash_equals($password, $data['password'])
            );

        if (! $passwordMatches) {
            throw ValidationException::withMessages([
                'email' => 'E-Mail oder Passwort ist ungueltig.',
            ]);
        }

        $account->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $account->id);

        return redirect()->route('geiser-portal.dashboard')->with('status', 'Sie sind im Il Coccolino-Serviceportal angemeldet.');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $account = $this->findGeiserAccountByEmail($data['email']);

        if ($account) {
            try {
                $plainToken = Str::random(64);
                $magicLink = CustomerPortalMagicLink::query()->create([
                    'customer_portal_account_id' => $account->id,
                    'token_hash' => hash('sha256', $plainToken),
                    'expires_at' => now()->addHours(12),
                ]);

                $url = route('geiser-portal.password.reset.form', ['token' => $plainToken]);

                Mail::raw("Guten Tag,\n\nueber den folgenden Link koennen Sie ein neues Passwort fuer das Il Coccolino-Serviceportal vergeben. Der Link ist 12 Stunden gueltig und kann nur einmal verwendet werden:\n\n{$url}\n\nFalls Sie diese Anfrage nicht gestellt haben, koennen Sie diese E-Mail ignorieren.\n", function ($message) use ($account): void {
                    $message->to($account->email)->subject('Neues Passwort fuer Il Coccolino-Serviceportal');
                });
            } catch (Throwable $exception) {
                Log::warning('Passwort-Reset-Mail fuer Geiser-Portal konnte nicht gesendet werden.', [
                    'account_id' => $account->id,
                    'magic_link_id' => $magicLink->id ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Falls fuer diese E-Mail ein Il Coccolino-Portalzugang existiert, wurde ein Link zur Passwortvergabe versendet.');
    }

    public function showPasswordResetForm(string $token): View|RedirectResponse
    {
        $magicLink = $this->usableGeiserMagicLink($token);
        if (! $magicLink) {
            return redirect()->route('geiser-portal.login')->with('warning', 'Der Link zur Passwortvergabe ist ungueltig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
        }

        return view('customer-portal-geiser.password-reset', [
            'token' => $token,
            'email' => $magicLink->account?->email,
        ]);
    }

    public function resetPassword(Request $request, string $token): RedirectResponse
    {
        $magicLink = $this->usableGeiserMagicLink($token);
        if (! $magicLink || ! $magicLink->account) {
            return redirect()->route('geiser-portal.login')->with('warning', 'Der Link zur Passwortvergabe ist ungueltig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $magicLink->account->forceFill([
            'password' => Hash::make($data['password']),
            'last_login_at' => now(),
        ])->save();
        $magicLink->forceFill(['used_at' => now()])->save();

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $magicLink->account->id);

        return redirect()->route('geiser-portal.dashboard')->with('status', 'Ihr Passwort wurde erfolgreich gesetzt und Sie sind nun angemeldet.');
    }

    public function consumeMagicLink(Request $request, string $token): RedirectResponse
    {
        $magicLink = $this->usableGeiserMagicLink($token);
        if (! $magicLink || ! $magicLink->account) {
            return redirect()->route('geiser-portal.login')->with('warning', 'Der Magic Link ist ungueltig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
        }

        $magicLink->forceFill(['used_at' => now()])->save();
        $magicLink->account->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $magicLink->account->id);

        return redirect()->route('geiser-portal.dashboard')->with('status', 'Sie sind im Il Coccolino-Serviceportal angemeldet.');
    }

    public function dashboard(Request $request): View
    {
        $account = $this->account($request);
        $tickets = Ticket::query()
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->with(['customerMachine', 'customerMachineProfile'])
            ->latest()
            ->get();

        return view('customer-portal-geiser.dashboard', [
            'account' => $account,
            'tickets' => $tickets,
            'customerStatusLabels' => $tickets->mapWithKeys(fn (Ticket $t) => [$t->id => $this->customerVisibleStatus($t)]),
        ]);
    }

    public function createTicket(Request $request): View
    {
        return view('customer-portal-geiser.tickets.create', [
            'account' => $this->account($request),
        ]);
    }

    public function history(Request $request): View
    {
        return view('customer-portal-geiser.history', [
            'account' => $this->account($request),
            'initialSerialNumber' => trim((string) $request->query('serial_number')),
        ]);
    }

    public function findMachineProfile(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:255'],
        ]);
        $serialNumber = trim($data['serial_number']);
        $history = $this->ticketHistoryData($account, $serialNumber);

        $profile = CustomerMachineProfile::query()
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->where('serial_number', $serialNumber)
            ->first();

        if (! $profile) {
            return response()->json(array_merge(['found' => false], $history));
        }

        return response()->json(array_merge([
            'found' => true,
            'profile' => [
                'serial_number' => $profile->serial_number,
                'contact_name' => $profile->contact_name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'street' => $profile->street,
                'zip' => $profile->zip,
                'city' => $profile->city,
                'manufacturer_snapshot' => $profile->manufacturer_snapshot,
                'machine_ref_snapshot' => $profile->machine_ref_snapshot,
                'warranty_claimed' => $profile->warranty_claimed,
                'accessory_presser_foot' => $profile->accessory_presser_foot,
                'accessory_bobbin_case' => $profile->accessory_bobbin_case,
                'accessory_bobbin' => $profile->accessory_bobbin,
                'accessory_power_cable' => $profile->accessory_power_cable,
                'accessory_foot_pedal' => $profile->accessory_foot_pedal,
                'accessory_case' => $profile->accessory_case,
                'accessory_other' => $profile->accessory_other,
                'repair_approval_limit' => $profile->repair_approval_limit,
                'intake_note' => $profile->intake_note,
            ],
        ], $history));
    }

    public function lookupTicketHistory(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:255'],
        ]);

        return response()->json($this->ticketHistoryData($account, trim($data['serial_number'])));
    }

    public function storeTicket(Request $request, DolibarrOrderSyncService $sync): RedirectResponse
    {
        $account = $this->account($request);

        // When a photo is uploaded, the typed fields are optional (user fills in later)
        $hasPhoto = $request->hasFile('customer_photo');
        $requiredOrNullable = fn() => $hasPhoto ? ['nullable', 'string'] : ['required', 'string'];

        $data = $request->validate([
            'manufacturer_snapshot' => ['nullable', 'string', 'max:255'],
            'machine_ref_snapshot'  => array_merge($requiredOrNullable(), ['max:255']),
            'serial_number'         => array_merge($requiredOrNullable(), ['max:255']),
            'contact_name'          => array_merge($requiredOrNullable(), ['max:255']),
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:255'],
            'warranty_claimed' => ['nullable', 'boolean'],
            'accessory_presser_foot' => ['nullable', 'boolean'],
            'accessory_bobbin_case' => ['nullable', 'boolean'],
            'accessory_bobbin' => ['nullable', 'boolean'],
            'accessory_power_cable' => ['nullable', 'boolean'],
            'accessory_foot_pedal' => ['nullable', 'boolean'],
            'accessory_case' => ['nullable', 'boolean'],
            'accessory_other' => ['nullable', 'string', 'max:255'],
            'repair_approval_limit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'error_description'     => $hasPhoto ? ['nullable', 'string'] : ['required', 'string'],
            'intake_note' => ['nullable', 'string'],
            'estimate_qty_tech' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'estimate_qty_service_fee' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'estimate_qty_vde' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'estimate_qty_consumables' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'customer_photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $serialNumber = trim((string) ($data['serial_number'] ?? ''));
        if ($serialNumber === '') {
            $serialNumber = 'PHOTO-'.Str::upper(Str::random(8));
        }
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        if ($contactName === '') {
            $contactName = $account->company_name ?: 'Portal-Kunde';
        }
        $machineRefSnapshot = trim((string) ($data['machine_ref_snapshot'] ?? ''));
        if ($machineRefSnapshot === '') {
            $machineRefSnapshot = 'Nicht angegeben';
        }
        $errorDescription = trim((string) ($data['error_description'] ?? ''));
        if ($errorDescription === '') {
            $errorDescription = 'Details siehe hochgeladenes Foto.';
        }

        [$estimateLines, $estimateTotal] = $this->buildEstimateSnapshot($data);

        $profile = CustomerMachineProfile::query()->updateOrCreate(
            [
                'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
                'serial_number' => $serialNumber,
            ],
            [
                'contact_name' => $contactName,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'street' => $data['street'] ?? null,
                'zip' => $data['zip'] ?? null,
                'city' => $data['city'] ?? null,
                'manufacturer_snapshot' => $data['manufacturer_snapshot'] ?? null,
                'machine_ref_snapshot' => $machineRefSnapshot,
                'warranty_claimed' => $request->boolean('warranty_claimed'),
                'accessory_presser_foot' => $request->boolean('accessory_presser_foot'),
                'accessory_bobbin_case' => $request->boolean('accessory_bobbin_case'),
                'accessory_bobbin' => $request->boolean('accessory_bobbin'),
                'accessory_power_cable' => $request->boolean('accessory_power_cable'),
                'accessory_foot_pedal' => $request->boolean('accessory_foot_pedal'),
                'accessory_case' => $request->boolean('accessory_case'),
                'accessory_other' => $data['accessory_other'] ?? null,
                'repair_approval_limit' => $data['repair_approval_limit'] ?? null,
                'intake_note' => $data['intake_note'] ?? null,
            ]
        );

        $machine = CustomerMachine::query()
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->where('dolibarr_machine_product_id', 0)
            ->where('serial_number', $profile->serial_number)
            ->firstOrNew([
                'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
                'dolibarr_machine_product_id' => 0,
                'serial_number' => $profile->serial_number,
            ]);

        $machine->forceFill([
            'customer_name_snapshot' => $account->company_name,
            'manufacturer_snapshot' => $profile->manufacturer_snapshot,
            'machine_ref_snapshot' => $profile->machine_ref_snapshot ?: ($data['machine_ref_snapshot'] ?? '—'),
        ])->save();

        $ticket = Ticket::query()->create([
            'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
            'customer_name_snapshot' => $account->company_name,
            'customer_contact_name_snapshot' => $profile->contact_name,
            'customer_email_snapshot' => $profile->email ?: $account->email,
            'customer_machine_id' => $machine->id,
            'customer_machine_profile_id' => $profile->id,
            'created_via_customer_portal' => true,
            'customer_portal_account_id' => $account->id,
            'service_enabled' => false,
            'cleaning' => false,
            'repair_enabled' => true,
            'spare_part_order_required' => false,
            'error_description' => $errorDescription,
            'customer_photo_path' => $this->storeCustomerPhoto($request),
            'customer_portal_estimate_lines' => $estimateLines,
            'customer_portal_estimate_total' => $estimateTotal,
            'acceptance_date' => now()->toDateString(),
            'target_date' => null,
            'status' => Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
        ]);

        try {
            $sync->ensureDraftOrder($ticket);
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());

            return redirect()
                ->route('geiser-portal.tickets.show', $ticket)
                ->with('warning', 'Ihr Ticket wurde gespeichert. Die interne Dolibarr-Synchronisierung muss noch geprueft werden.')
                ->with('auto_open_print_url', route('geiser-portal.tickets.print', $ticket));
        }

        return redirect()
            ->route('geiser-portal.tickets.show', $ticket)
            ->with('status', 'Ihr Ticket wurde erstellt.')
            ->with('auto_open_print_url', route('geiser-portal.tickets.print', $ticket));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerateToken();

        return redirect()->route('geiser-portal.home')->with('status', 'Sie wurden abgemeldet.');
    }

    public function showTicket(Request $request, Ticket $ticket): View
    {
        $account = $this->account($request);

        if (! $this->canViewTicket($account, $ticket)) {
            abort(403);
        }

        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines', 'messages.attachments']);
        $estimateLines = $ticket->customer_portal_estimate_lines ?: $this->buildEstimateSnapshot([])[0];
        $estimateTotal = $ticket->customer_portal_estimate_total;

        if ($estimateTotal === null) {
            $estimateTotal = array_reduce($estimateLines, fn (float $sum, array $line) => $sum + (float) ($line['line_total'] ?? 0), 0.0);
        }

        return view('customer-portal-geiser.tickets.show', [
            'account' => $account,
            'ticket' => $ticket,
            'isEditable' => $this->canEditTicket($account, $ticket),
            'customerStatusLabel' => $this->customerVisibleStatus($ticket),
            'estimateLines' => $estimateLines,
            'estimateTotal' => $estimateTotal,
            'customerEmail' => trim((string) ($ticket->customerMachineProfile?->email
                ?: $ticket->customer_email_snapshot
                ?: $account->email
                ?: '')),
        ]);
    }

    public function printTicket(Request $request, Ticket $ticket)
    {
        $account = $this->account($request);

        if (! $this->canViewTicket($account, $ticket)) {
            abort(403);
        }

        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);
        $estimateLines = $ticket->customer_portal_estimate_lines ?: $this->buildEstimateSnapshot([])[0];
        $estimateTotal = $ticket->customer_portal_estimate_total;

        if ($estimateTotal === null) {
            $estimateTotal = array_reduce($estimateLines, fn (float $sum, array $line) => $sum + (float) ($line['line_total'] ?? 0), 0.0);
        }

        $payload = [
            'account' => $account,
            'ticket' => $ticket,
            'estimateLines' => $estimateLines,
            'estimateTotal' => $estimateTotal,
            'customerStatusLabel' => $this->customerVisibleStatus($ticket),
            'generatedAt' => now(),
        ];

        $fileName = 'geiser-ticket-'.$ticket->ticket_number.'.pdf';

        if (! class_exists(Pdf::class)) {
            return response()->view('customer-portal-geiser.tickets.print', $payload);
        }

        $pdf = Pdf::loadView('customer-portal-geiser.tickets.print', $payload)->setPaper('a4', 'portrait');

        return $pdf->stream($fileName);
    }

    public function generateWorkReport(Request $request, Ticket $ticket, GeiserInvoiceCalculator $invoiceCalculator)
    {
        $account = $this->account($request);

        if (! $this->canViewTicket($account, $ticket)) {
            abort(403);
        }

        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);
        $summary = $invoiceCalculator->summarize($ticket);
        $invoiceLines = $invoiceCalculator->withCopyTexts($ticket, $summary['invoiceLines']);

        $payload = $this->buildWorkReportPayload($ticket, $invoiceLines);
        $fileName = 'arbeitsbericht-'.$ticket->ticket_number.'.pdf';

        if (! class_exists(Pdf::class)) {
            return response()->view('customer-portal-geiser.tickets.work-report', $payload);
        }

        $pdf = Pdf::loadView('customer-portal-geiser.tickets.work-report', $payload)->setPaper('a4', 'portrait');

        return $pdf->stream($fileName);
    }

    public function mailWorkReport(Request $request, Ticket $ticket, GeiserInvoiceCalculator $invoiceCalculator): RedirectResponse
    {
        $account = $this->account($request);

        if (! $this->canViewTicket($account, $ticket)) {
            abort(403);
        }

        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);
        $summary = $invoiceCalculator->summarize($ticket);
        $invoiceLines = $invoiceCalculator->withCopyTexts($ticket, $summary['invoiceLines']);

        $recipientEmail = trim((string) ($ticket->customerMachineProfile?->email
            ?: $ticket->customer_email_snapshot
            ?: $account->email
            ?: ''));

        if ($recipientEmail === '') {
            return redirect()->route('geiser-portal.tickets.show', $ticket)
                ->with('warning', 'Es ist keine E-Mail-Adresse hinterlegt. Der Arbeitsbericht konnte nicht versendet werden.');
        }

        if (! class_exists(Pdf::class)) {
            return redirect()->route('geiser-portal.tickets.show', $ticket)
                ->with('warning', 'PDF-Generierung ist nicht verfuegbar.');
        }

        $payload = $this->buildWorkReportPayload($ticket, $invoiceLines);
        $fileName = 'arbeitsbericht-'.$ticket->ticket_number.'.pdf';
        $pdfBinary = Pdf::loadView('customer-portal-geiser.tickets.work-report', $payload)
            ->setPaper('a4', 'portrait')
            ->output();

        $this->sendWorkReportByMail($ticket, $recipientEmail, $fileName, $pdfBinary);

        return redirect()->route('geiser-portal.tickets.show', $ticket)
            ->with('status', 'Der Arbeitsbericht wurde per E-Mail an '.$recipientEmail.' gesendet.');
    }

    public function updateTicket(Request $request, Ticket $ticket): RedirectResponse
    {
        $account = $this->account($request);

        if (! $this->canEditTicket($account, $ticket)) {
            abort(403);
        }

        $hasPhoto = $request->hasFile('customer_photo') || filled($ticket->customer_photo_path);
        $requiredOrNullable = fn () => $hasPhoto ? ['nullable', 'string'] : ['required', 'string'];

        $data = $request->validate([
            'manufacturer_snapshot' => ['nullable', 'string', 'max:255'],
            'machine_ref_snapshot' => array_merge($requiredOrNullable(), ['max:255']),
            'serial_number' => array_merge($requiredOrNullable(), ['max:255']),
            'error_description' => $hasPhoto ? ['nullable', 'string'] : ['required', 'string'],
            'contact_name' => array_merge($requiredOrNullable(), ['max:255']),
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:255'],
            'warranty_claimed' => ['nullable', 'boolean'],
            'accessory_presser_foot' => ['nullable', 'boolean'],
            'accessory_bobbin_case' => ['nullable', 'boolean'],
            'accessory_bobbin' => ['nullable', 'boolean'],
            'accessory_power_cable' => ['nullable', 'boolean'],
            'accessory_foot_pedal' => ['nullable', 'boolean'],
            'accessory_case' => ['nullable', 'boolean'],
            'accessory_other' => ['nullable', 'string', 'max:255'],
            'repair_approval_limit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'intake_note' => ['nullable', 'string'],
            'estimate_qty_tech' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'estimate_qty_service_fee' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'estimate_qty_vde' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'estimate_qty_consumables' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'customer_photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $currentProfile = $ticket->customerMachineProfile;
        $currentMachine = $ticket->customerMachine;

        $serialNumber = trim((string) ($data['serial_number']
            ?? $currentProfile?->serial_number
            ?? $currentMachine?->serial_number
            ?? ''));
        if ($serialNumber === '') {
            $serialNumber = 'PHOTO-'.Str::upper(Str::random(8));
        }

        $contactName = trim((string) ($data['contact_name']
            ?? $currentProfile?->contact_name
            ?? $ticket->customer_contact_name_snapshot
            ?? ''));
        if ($contactName === '') {
            $contactName = $account->company_name ?: 'Portal-Kunde';
        }

        $machineRefSnapshot = trim((string) ($data['machine_ref_snapshot']
            ?? $currentProfile?->machine_ref_snapshot
            ?? $currentMachine?->machine_ref_snapshot
            ?? ''));
        if ($machineRefSnapshot === '') {
            $machineRefSnapshot = 'Nicht angegeben';
        }

        $manufacturerSnapshot = trim((string) ($data['manufacturer_snapshot']
            ?? $currentProfile?->manufacturer_snapshot
            ?? $currentMachine?->manufacturer_snapshot
            ?? ''));

        $errorDescription = trim((string) ($data['error_description'] ?? $ticket->error_description ?? ''));
        if ($errorDescription === '') {
            $errorDescription = 'Details siehe hochgeladenes Foto.';
        }

        [$estimateLines, $estimateTotal] = $this->buildEstimateSnapshot($data);

        $profile = CustomerMachineProfile::query()->updateOrCreate(
            [
                'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
                'serial_number' => $serialNumber,
            ],
            [
                'contact_name' => $contactName,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'street' => $data['street'] ?? null,
                'zip' => $data['zip'] ?? null,
                'city' => $data['city'] ?? null,
                'manufacturer_snapshot' => $manufacturerSnapshot !== '' ? $manufacturerSnapshot : null,
                'machine_ref_snapshot' => $machineRefSnapshot,
                'warranty_claimed' => $request->boolean('warranty_claimed'),
                'accessory_presser_foot' => $request->boolean('accessory_presser_foot'),
                'accessory_bobbin_case' => $request->boolean('accessory_bobbin_case'),
                'accessory_bobbin' => $request->boolean('accessory_bobbin'),
                'accessory_power_cable' => $request->boolean('accessory_power_cable'),
                'accessory_foot_pedal' => $request->boolean('accessory_foot_pedal'),
                'accessory_case' => $request->boolean('accessory_case'),
                'accessory_other' => $data['accessory_other'] ?? null,
                'repair_approval_limit' => $data['repair_approval_limit'] ?? null,
                'intake_note' => $data['intake_note'] ?? null,
            ]
        );

        $machine = CustomerMachine::query()
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->where('dolibarr_machine_product_id', 0)
            ->where('serial_number', $serialNumber)
            ->firstOrNew([
                'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
                'dolibarr_machine_product_id' => 0,
                'serial_number' => $serialNumber,
            ]);

        $machine->forceFill([
            'customer_name_snapshot' => $account->company_name,
            'manufacturer_snapshot' => $manufacturerSnapshot !== '' ? $manufacturerSnapshot : null,
            'machine_ref_snapshot' => $machineRefSnapshot,
        ])->save();

        $updateData = [
            'customer_machine_id' => $machine->id,
            'customer_machine_profile_id' => $profile->id,
            'customer_contact_name_snapshot' => $contactName,
            'customer_email_snapshot' => $data['email'] ?? $account->email,
            'error_description' => $errorDescription,
            'customer_portal_estimate_lines' => $estimateLines,
            'customer_portal_estimate_total' => $estimateTotal,
        ];
        $newPhotoPath = $this->storeCustomerPhoto($request);
        if ($newPhotoPath !== null) {
            if ($ticket->customer_photo_path) {
                Storage::disk('public')->delete($ticket->customer_photo_path);
            }
            $updateData['customer_photo_path'] = $newPhotoPath;
        }
        $ticket->update($updateData);

        return redirect()->route('geiser-portal.tickets.show', $ticket)
            ->with('status', 'Ihr Ticket wurde aktualisiert.');
    }

    /**
     * Upload and scan image with OCR to extract form data
     */
    public function scanTicketImage(Request $request): JsonResponse
    {
        $this->account($request); // Ensure user is logged in

        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        try {
            $image = $request->file('image');
            $imagePath = $image->store('ocr-temp', 'local');
            $fullPath = Storage::disk('local')->path($imagePath);

            // Scan image with OCR
            $ocrService = new OcrService();
            $ocrResult = $ocrService->scanImage($fullPath);

            // Delete temp image
            Storage::disk('local')->delete($imagePath);

            if ($ocrResult['status'] !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OCR scan failed: ' . ($ocrResult['message'] ?? 'Unknown error'),
                ], 400);
            }

            // Parse OCR text into structured fields
            $parser = new OcrDataParser();
            $extracted = $parser->parseGeiserForm($ocrResult['full_text']);

            return response()->json([
                'status' => 'success',
                'extracted' => $extracted,
                'raw_text' => $ocrResult['full_text'],
            ]);
        } catch (Exception $e) {
            Log::error('OCR scan error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'OCR scan failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    private function buildWorkReportPayload(Ticket $ticket, Collection $invoiceLines): array
    {
        return [
            'ticket' => $ticket,
            'invoiceLines' => $invoiceLines,
            'manufacturer' => trim((string) ($ticket->customerMachine?->manufacturer_snapshot ?: $ticket->customerMachineProfile?->manufacturer_snapshot ?: '')),
            'machineRef' => trim((string) ($ticket->customerMachine?->machine_ref_snapshot ?: $ticket->customerMachineProfile?->machine_ref_snapshot ?: '')),
            'serialNumber' => trim((string) ($ticket->customerMachine?->serial_number ?: $ticket->customerMachineProfile?->serial_number ?: '-')),
            'generatedAt' => now(),
        ];
    }

    private function sendWorkReportByMail(Ticket $ticket, string $recipientEmail, string $fileName, string $pdfBinary): void
    {
        $fromAddress = (string) config('geiser_invoice.mail.from_address', 'service@example.com');
        $fromName = (string) config('geiser_invoice.work_report_mail.from_name', 'Il Coccolino');
        $subject = 'Ihr Arbeitsbericht - Ticket '.$ticket->ticket_number;
        $body = "Guten Tag,\n\nanbei erhalten Sie den Arbeitsbericht zu Ihrem Service-Ticket "
            .$ticket->ticket_number.".\n\nBei Fragen stehen wir gerne zur Verfuegung.\n\nMit freundlichen Gruessen";

        try {
            Mail::raw($body, function ($message) use ($recipientEmail, $subject, $fromAddress, $fromName, $pdfBinary, $fileName): void {
                $message->to($recipientEmail)
                    ->from($fromAddress, $fromName)
                    ->subject($subject)
                    ->attachData($pdfBinary, $fileName, ['mime' => 'application/pdf']);
            });
        } catch (Throwable $exception) {
            Log::warning('Geiser-Arbeitsbericht konnte nicht per E-Mail versendet werden.', [
                'ticket_id' => $ticket->id,
                'recipient' => $recipientEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Store compressed customer photo to public disk.
     * Client-side already compressed; we just store it.
     * Returns the stored path or null if no file was uploaded.
     */
    private function storeCustomerPhoto(Request $request): ?string
    {
        if (!$request->hasFile('customer_photo')) {
            return null;
        }
        $file = $request->file('customer_photo');
        return $file->store('ticket-photos', 'public');
    }
    private function optionalAccount(Request $request): ?CustomerPortalAccount
    {
        $accountId = (int) $request->session()->get(self::SESSION_KEY);

        if ($accountId <= 0) {
            return null;
        }

        return CustomerPortalAccount::query()
            ->whereKey($accountId)
            ->where('dolibarr_thirdparty_id', self::CUSTOMER_ID)
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_GEISER)
            ->where('is_active', true)
            ->first();
    }

    private function account(Request $request): CustomerPortalAccount
    {
        return CustomerPortalAccount::query()
            ->whereKey((int) $request->session()->get(self::SESSION_KEY))
            ->where('dolibarr_thirdparty_id', self::CUSTOMER_ID)
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_GEISER)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function findGeiserAccountByEmail(string $email): ?CustomerPortalAccount
    {
        return CustomerPortalAccount::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where('dolibarr_thirdparty_id', self::CUSTOMER_ID)
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_GEISER)
            ->where('is_active', true)
            ->first();
    }

    private function usableGeiserMagicLink(string $token): ?CustomerPortalMagicLink
    {
        $magicLink = CustomerPortalMagicLink::query()
            ->with('account')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (
            ! $magicLink
            || ! $magicLink->isUsable()
            || ! $magicLink->account?->is_active
            || ! $magicLink->account->isGeiserPortal()
            || (int) $magicLink->account->dolibarr_thirdparty_id !== self::CUSTOMER_ID
        ) {
            return null;
        }

        return $magicLink;
    }

    private function canViewTicket(CustomerPortalAccount $account, Ticket $ticket): bool
    {
        return (int) $ticket->dolibarr_customer_id === (int) $account->dolibarr_thirdparty_id;
    }

    private function canEditTicket(CustomerPortalAccount $account, Ticket $ticket): bool
    {
        return $this->canViewTicket($account, $ticket)
            && $ticket->status === Ticket::STATUS_OPEN;
    }

    private function customerVisibleStatus(Ticket $ticket): string
    {
        // intern erledigt ist für Kunden nicht sichtbar → bleibt "in Bearbeitung"
        if ($ticket->status === Ticket::STATUS_INTERNALLY_DONE) {
            return 'in Bearbeitung';
        }
        return $ticket->statusLabel();
    }

    private function ticketHistoryData(CustomerPortalAccount $account, string $serialNumber): array
    {
        $tickets = Ticket::query()
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->where(function ($query) use ($serialNumber): void {
                $query->whereHas('customerMachineProfile', function ($profileQuery) use ($serialNumber): void {
                    $profileQuery->where('serial_number', $serialNumber);
                })->orWhereHas('customerMachine', function ($machineQuery) use ($serialNumber): void {
                    $machineQuery->where('serial_number', $serialNumber);
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
                    'status_label' => $this->customerVisibleStatus($ticket),
                    'acceptance_date' => $ticket->acceptance_date?->format('d.m.Y'),
                    'created_at' => $ticket->created_at?->format('d.m.Y H:i'),
                    'machine_label' => $ticket->customerMachine?->displayName()
                        ?: trim(($ticket->customerMachineProfile?->manufacturer_snapshot ? $ticket->customerMachineProfile->manufacturer_snapshot.' / ' : '').($ticket->customerMachineProfile?->machine_ref_snapshot ?: '-')),
                    'contact_name' => $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot ?: '-',
                    'created_via_customer_portal' => (bool) $ticket->created_via_customer_portal,
                    'url' => route('geiser-portal.tickets.show', $ticket),
                ])->all(),
            ],
        ];
    }

    private function buildEstimateSnapshot(array $data): array
    {
        $lines = [];
        $total = 0.0;

        foreach (self::ESTIMATE_DEFINITIONS as $field => $meta) {
            $qty = max(0, (float) ($data[$field] ?? self::ESTIMATE_DEFAULT_QUANTITIES[$field] ?? 0));
            $lineTotal = $qty * (float) $meta['unit_price'];
            $total += $lineTotal;

            $lines[] = [
                'key' => $field,
                'label' => $meta['label'],
                'hint' => $meta['hint'],
                'quantity' => round($qty, 2),
                'unit_price' => round((float) $meta['unit_price'], 2),
                'line_total' => round($lineTotal, 2),
            ];
        }

        return [$lines, round($total, 2)];
    }
}
