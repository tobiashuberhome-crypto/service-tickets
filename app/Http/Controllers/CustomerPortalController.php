<?php

namespace App\Http\Controllers;

use App\Models\CustomerMachine;
use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalMagicLink;
use App\Models\CustomerPortalRequest;
use App\Models\Ticket;
use App\Services\Tickets\DolibarrOrderSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CustomerPortalController extends Controller
{
    public function home(): View
    {
        return view('customer-portal.home');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:255'],
            'machine_serial' => ['nullable', 'string', 'max:255'],
            'customer_number_input' => ['nullable', 'string', 'max:255'],
            'invoice_or_order_number' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
        ]);

        CustomerPortalRequest::query()->create($data + [
            'status' => CustomerPortalRequest::STATUS_NEW,
        ]);

        return redirect()->route('customer-portal.home')->with('status', 'Ihre Anfrage wurde gesendet. Wir pruefen Ihre Daten intern und schalten den Zugang anschliessend frei.');
    }

    public function login(): View
    {
        return view('customer-portal.login');
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $account = CustomerPortalAccount::query()
            ->where('email', mb_strtolower($data['email']))
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_DEFAULT)
            ->where('is_active', true)
            ->first();

        if ($account) {
            $plainToken = Str::random(64);
            $magicLink = CustomerPortalMagicLink::query()->create([
                'customer_portal_account_id' => $account->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(30),
            ]);

            $url = route('customer-portal.magic', ['token' => $plainToken]);

            try {
                Mail::raw("Guten Tag,\n\nueber den folgenden Link koennen Sie sich im Kundenportal anmelden. Der Link ist 30 Minuten gueltig und kann nur einmal verwendet werden:\n\n{$url}\n\nFalls Sie diesen Link nicht angefordert haben, koennen Sie diese E-Mail ignorieren.\n", function ($message) use ($account): void {
                    $message->to($account->email)->subject('Ihr Zugang zum Kundenportal');
                });
            } catch (Throwable $exception) {
                Log::warning('Magic-Link-Mail konnte nicht gesendet werden.', [
                    'account_id' => $account->id,
                    'magic_link_id' => $magicLink->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Falls ein freigegebener Zugang zu dieser E-Mail existiert, wurde ein Magic Link versendet.');
    }

    public function consumeMagicLink(Request $request, string $token): RedirectResponse
    {
        $magicLink = CustomerPortalMagicLink::query()
            ->with('account')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (
            ! $magicLink
            || ! $magicLink->isUsable()
            || ! $magicLink->account?->is_active
            || ! $magicLink->account->isDefaultPortal()
        ) {
            return redirect()->route('customer-portal.login')->with('warning', 'Der Magic Link ist ungueltig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
        }

        $magicLink->forceFill(['used_at' => now()])->save();
        $magicLink->account->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();
        $request->session()->put('customer_portal_account_id', $magicLink->account->id);

        return redirect()->route('customer-portal.dashboard')->with('status', 'Sie sind im Kundenportal angemeldet.');
    }

    public function dashboard(Request $request): View
    {
        $account = $this->account($request);
        $tickets = Ticket::query()
            ->where('customer_portal_account_id', $account->id)
            ->with('customerMachine')
            ->latest()
            ->get();

        return view('customer-portal.dashboard', compact('account', 'tickets'));
    }

    public function createTicket(Request $request): View
    {
        return view('customer-portal.tickets.create', [
            'account' => $this->account($request),
        ]);
    }

    public function storeTicket(Request $request, DolibarrOrderSyncService $sync): RedirectResponse
    {
        $account = $this->account($request);
        $data = $request->validate([
            'manufacturer_snapshot' => ['nullable', 'string', 'max:255'],
            'machine_ref_snapshot' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'service_enabled' => ['nullable', 'boolean'],
            'cleaning' => ['nullable', 'boolean'],
            'repair_enabled' => ['nullable', 'boolean'],
            'spare_part_order_required' => ['nullable', 'boolean'],
            'error_description' => ['required', 'string'],
        ]);

        if (! $request->boolean('service_enabled') && ! $request->boolean('repair_enabled')) {
            throw ValidationException::withMessages([
                'service_enabled' => 'Bitte waehlen Sie mindestens Service oder Reparatur aus.',
            ]);
        }

        $machine = CustomerMachine::query()
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->where('dolibarr_machine_product_id', 0)
            ->where('machine_ref_snapshot', $data['machine_ref_snapshot'])
            ->where('serial_number', $data['serial_number'] ?? null)
            ->firstOrNew([
                'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
                'dolibarr_machine_product_id' => 0,
                'serial_number' => $data['serial_number'] ?? null,
            ]);

        $machine->forceFill([
            'customer_name_snapshot' => $account->company_name,
            'manufacturer_snapshot' => $data['manufacturer_snapshot'] ?? null,
            'machine_ref_snapshot' => $data['machine_ref_snapshot'],
        ])->save();

        $ticket = Ticket::query()->create([
            'dolibarr_customer_id' => $account->dolibarr_thirdparty_id,
            'customer_name_snapshot' => $account->company_name,
            'customer_contact_name_snapshot' => $account->contact_name,
            'customer_email_snapshot' => $account->email,
            'customer_machine_id' => $machine->id,
            'created_via_customer_portal' => true,
            'customer_portal_account_id' => $account->id,
            'service_enabled' => $request->boolean('service_enabled'),
            'cleaning' => $request->boolean('cleaning'),
            'repair_enabled' => $request->boolean('repair_enabled'),
            'spare_part_order_required' => $request->boolean('spare_part_order_required'),
            'error_description' => $data['error_description'],
            'acceptance_date' => now()->toDateString(),
            'target_date' => null,
            'status' => Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
        ]);

        try {
            $sync->ensureDraftOrder($ticket);
            $sync->prepareServiceLines($ticket);
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());

            return redirect()->route('customer-portal.dashboard')->with('warning', 'Ihr Ticket wurde gespeichert. Die interne Dolibarr-Synchronisierung muss noch geprueft werden.');
        }

        return redirect()->route('customer-portal.dashboard')->with('status', 'Ihr Ticket wurde erstellt.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('customer_portal_account_id');
        $request->session()->regenerateToken();

        return redirect()->route('customer-portal.home')->with('status', 'Sie wurden abgemeldet.');
    }

    private function account(Request $request): CustomerPortalAccount
    {
        return CustomerPortalAccount::query()->findOrFail((int) $request->session()->get('customer_portal_account_id'));
    }
}
