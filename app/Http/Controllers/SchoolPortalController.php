<?php

namespace App\Http\Controllers;

use App\Models\CustomerMachine;
use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalMagicLink;
use App\Models\SchoolRoom;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SchoolPortalController extends Controller
{
    private const SESSION_KEY = 'school_portal_account_id';

    public function home(Request $request): View
    {
        return view('school-portal.home', [
            'account' => $this->optionalAccount($request),
        ]);
    }

    public function login(): View
    {
        return view('school-portal.login');
    }

    public function loginWithPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $account = CustomerPortalAccount::where('email', $data['email'])
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_SCHOOL)
            ->where('is_active', true)
            ->first();

        if (! $account || blank($account->password) || ! Hash::check($data['password'], $account->password)) {
            return back()
                ->withErrors(['email' => 'Die Kombination aus E-Mail und Passwort ist ungueltig.'])
                ->onlyInput('email');
        }

        $account->update(['last_login_at' => now()]);
        $request->session()->put(self::SESSION_KEY, $account->id);

        return redirect()->route('school-portal.dashboard')->with('status', 'Willkommen, '.$account->company_name.'!');
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $account = CustomerPortalAccount::where('email', $data['email'])
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_SCHOOL)
            ->where('is_active', true)
            ->first();

        if ($account) {
            $plainToken = Str::random(64);
            CustomerPortalMagicLink::create([
                'customer_portal_account_id' => $account->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(30),
            ]);

            $url = route('school-portal.magic', ['token' => $plainToken]);

            try {
                Mail::raw(
                    "Guten Tag,\n\nueber den folgenden Link koennen Sie sich im Schul-Serviceportal anmelden.\nDer Link ist 30 Minuten gueltig und kann nur einmal verwendet werden:\n\n{$url}\n\nFalls Sie diesen Link nicht angefordert haben, koennen Sie diese E-Mail ignorieren.\n",
                    fn ($m) => $m->to($account->email)->subject('Ihr Zugang zum Schul-Serviceportal')
                );
            } catch (Throwable $e) {
                Log::warning('Magic-Link-Mail fuer Schul-Portal konnte nicht gesendet werden.', ['error' => $e->getMessage()]);
            }
        }

        return redirect()
            ->route('school-portal.login')
            ->with('status', 'Falls Ihre E-Mail-Adresse bei uns hinterlegt ist, erhalten Sie in Kuerze einen Link.');
    }

    public function consumeMagicLink(Request $request, string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);
        $link = CustomerPortalMagicLink::where('token_hash', $hash)
            ->where('expires_at', '>', now())
            ->whereNull('consumed_at')
            ->first();

        if (! $link) {
            return redirect()->route('school-portal.login')->with('error', 'Dieser Link ist ungueltig oder abgelaufen.');
        }

        $account = $link->account;
        if (! $account || $account->portal_scope !== CustomerPortalAccount::PORTAL_SCOPE_SCHOOL || ! $account->is_active) {
            return redirect()->route('school-portal.login')->with('error', 'Kein gueltiges Konto gefunden.');
        }

        $link->update(['consumed_at' => now()]);
        $account->update(['last_login_at' => now()]);
        $request->session()->put(self::SESSION_KEY, $account->id);

        return redirect()->route('school-portal.dashboard')->with('status', 'Willkommen, '.$account->company_name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('school-portal.home')->with('status', 'Sie wurden abgemeldet.');
    }

    public function dashboard(Request $request): View
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);

        $rooms = SchoolRoom::where('customer_portal_account_id', $ownerAccount->id)
            ->with(['machines' => fn ($q) => $q->with(['tickets' => fn ($q) => $q->whereNotIn('status', [Ticket::STATUS_DONE, Ticket::STATUS_DELIVERED])])])
            ->orderBy('name')
            ->get();

        $totalMachines = $rooms->sum(fn ($r) => $r->machines->count());
        $machinesWithOpenTickets = $rooms->sum(fn ($r) => $r->machines->filter(fn ($m) => $m->tickets->isNotEmpty())->count());
        $openTickets = $rooms->sum(fn ($r) => $r->machines->sum(fn ($m) => $m->tickets->count()));

        return view('school-portal.dashboard', compact('account', 'rooms', 'totalMachines', 'machinesWithOpenTickets', 'openTickets'));
    }

    public function showRoom(Request $request, SchoolRoom $room): View
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        abort_unless($room->customer_portal_account_id === $ownerAccount->id, 403);

        $machines = $room->machines()
            ->with(['tickets' => fn ($q) => $q->latest()->take(1)])
            ->orderBy('machine_ref_snapshot')
            ->get()
            ->map(function (CustomerMachine $m): CustomerMachine {
                $m->latest_ticket = $m->tickets->first();
                $m->status_label = $this->machineStatusLabel($m);
                $m->status_class = $this->machineStatusClass($m);
                return $m;
            });

        return view('school-portal.room', compact('account', 'room', 'machines'));
    }

    public function showMachine(Request $request, CustomerMachine $machine): View
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        $room = $machine->schoolRoom;
        abort_unless($room && $room->customer_portal_account_id === $ownerAccount->id, 403);

        $this->ensureMachineQrToken($machine);

        $tickets = $machine->tickets()->latest()->get();
        $qrPublicUrl = route('school-portal.qr.form', ['token' => $machine->qr_token]);

        return view('school-portal.machine', compact('account', 'machine', 'room', 'tickets', 'qrPublicUrl'));
    }

    public function regenerateMachineQr(Request $request, CustomerMachine $machine): RedirectResponse
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        $room = $machine->schoolRoom;
        abort_unless($room && $room->customer_portal_account_id === $ownerAccount->id, 403);

        $machine->forceFill(['qr_token' => $this->generateUniqueQrToken()])->save();

        return redirect()->route('school-portal.machines.show', $machine)->with('status', 'QR-Code wurde neu erzeugt.');
    }

    public function qrReportForm(string $token): View
    {
        $machine = CustomerMachine::with('schoolRoom')->where('qr_token', $token)->firstOrFail();
        $room = $machine->schoolRoom;
        abort_unless($room, 404);

        return view('school-portal.qr-report', compact('machine', 'room', 'token'));
    }

    public function qrReportSubmit(Request $request, string $token): RedirectResponse
    {
        $machine = CustomerMachine::with('schoolRoom')->where('qr_token', $token)->firstOrFail();
        $room = $machine->schoolRoom;
        abort_unless($room, 404);

        $data = $request->validate([
            'problem_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $request->input('problem_type') === 'Sonstiges')],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $existing = Ticket::query()
            ->where('customer_machine_id', $machine->id)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_INTERNALLY_DONE])
            ->where('error_description', 'like', 'Problem: '.$data['problem_type'].'%')
            ->latest('id')
            ->first();

        if ($existing) {
            return redirect()->route('school-portal.qr.form', ['token' => $token])
                ->with('warning', 'Fuer dieses Problem besteht bereits ein offenes Ticket.');
        }

        $ownerAccount = CustomerPortalAccount::find($room->customer_portal_account_id);

        $description = 'Problem: '.$data['problem_type'];
        if (! empty($data['description'])) {
            $description .= "\n".$data['description'];
        }
        $description .= "\n\nMeldung via QR-Code";
        $description .= "\nRaum: ".$room->name;
        $description .= "\nKontakt: ".$data['contact_name'];
        if (! empty($data['contact_email'])) {
            $description .= "\nKontakt-E-Mail: ".$data['contact_email'];
        }

        Ticket::create([
            'dolibarr_customer_id' => $ownerAccount?->dolibarr_thirdparty_id,
            'customer_name_snapshot' => $ownerAccount?->company_name ?? ($machine->customer_name_snapshot ?? 'Schule'),
            'customer_machine_id' => $machine->id,
            'customer_portal_account_id' => $ownerAccount?->id,
            'created_via_customer_portal' => true,
            'service_enabled' => false,
            'cleaning' => false,
            'repair_enabled' => true,
            'spare_part_order_required' => false,
            'error_description' => $description,
            'acceptance_date' => now()->toDateString(),
            'status' => Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
        ]);

        return redirect()->route('school-portal.qr.form', ['token' => $token])
            ->with('status', 'Meldung wurde erfolgreich gesendet. Vielen Dank.');
    }

    public function createTicket(Request $request, CustomerMachine $machine): View
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        $room = $machine->schoolRoom;
        abort_unless($room && $room->customer_portal_account_id === $ownerAccount->id, 403);

        return view('school-portal.tickets.create', compact('account', 'machine', 'room'));
    }

    public function storeTicket(Request $request, CustomerMachine $machine): RedirectResponse
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        $room = $machine->schoolRoom;
        abort_unless($room && $room->customer_portal_account_id === $ownerAccount->id, 403);

        $data = $request->validate([
            'problem_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,normal,urgent'],
        ]);

        $description = 'Problem: '.$data['problem_type'];
        if (! empty($data['description'])) {
            $description .= "\n".$data['description'];
        }
        $description .= "\n\nPrioritaet: ".match ($data['priority']) {
            'low' => 'niedrig',
            'urgent' => 'dringend',
            default => 'normal',
        };
        $description .= "\nRaum: ".$room->name;
        $description .= "\nSchule: ".$account->company_name;

        Ticket::create([
            'dolibarr_customer_id' => $ownerAccount->dolibarr_thirdparty_id,
            'customer_name_snapshot' => $ownerAccount->company_name,
            'customer_machine_id' => $machine->id,
            'customer_portal_account_id' => $ownerAccount->id,
            'created_via_customer_portal' => true,
            'service_enabled' => false,
            'cleaning' => false,
            'repair_enabled' => true,
            'spare_part_order_required' => false,
            'error_description' => $description,
            'acceptance_date' => now()->toDateString(),
            'status' => Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
        ]);

        return redirect()
            ->route('school-portal.machines.show', $machine)
            ->with('status', 'Ticket wurde erfolgreich erstellt. Wir melden uns zur weiteren Abstimmung.');
    }

    public function storeMachine(Request $request, SchoolRoom $room): RedirectResponse
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        abort_unless($room->customer_portal_account_id === $ownerAccount->id, 403);

        $data = $request->validate([
            'machine_ref_snapshot' => ['required', 'string', 'max:200'],
            'manufacturer_snapshot' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'inventory_number' => ['nullable', 'string', 'max:100'],
        ]);

        $ref = $data['machine_ref_snapshot'];
        if (! empty($data['inventory_number'])) {
            $ref .= ' (Inv: '.$data['inventory_number'].')';
        }

        CustomerMachine::create([
            'school_room_id' => $room->id,
            'dolibarr_customer_id' => $ownerAccount->dolibarr_thirdparty_id,
            'customer_name_snapshot' => $ownerAccount->company_name,
            'dolibarr_machine_product_id' => 0,
            'manufacturer_snapshot' => $data['manufacturer_snapshot'] ?? null,
            'machine_ref_snapshot' => $ref,
            'serial_number' => $data['serial_number'] ?? null,
            'qr_token' => $this->generateUniqueQrToken(),
        ]);

        return redirect()
            ->route('school-portal.rooms.show', $room)
            ->with('status', 'Maschine wurde erfasst.');
    }

    public function users(Request $request): View
    {
        $account = $this->requireAccount($request);

        $users = $this->schoolAccountsQuery($account)
            ->orderByDesc('is_active')
            ->orderBy('contact_name')
            ->orderBy('email')
            ->get();

        return view('school-portal.users', compact('account', 'users'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $account = $this->requireAccount($request);

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customer_portal_accounts', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'initial_password' => ['required', 'string', 'min:8', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Diese E-Mail-Adresse ist bereits vergeben.',
        ]);

        CustomerPortalAccount::create([
            'dolibarr_thirdparty_id' => $account->dolibarr_thirdparty_id,
            'dolibarr_customer_code' => $account->dolibarr_customer_code,
            'company_name' => $account->company_name,
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['initial_password']),
            'portal_scope' => CustomerPortalAccount::PORTAL_SCOPE_SCHOOL,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('school-portal.users.index')->with('status', 'Benutzer wurde angelegt.');
    }

    public function updateUser(Request $request, CustomerPortalAccount $user): RedirectResponse
    {
        $account = $this->requireAccount($request);
        abort_unless($this->schoolAccountsQuery($account)->whereKey($user->id)->exists(), 403);

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customer_portal_accounts', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'new_password' => ['nullable', 'string', 'min:8', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Diese E-Mail-Adresse ist bereits vergeben.',
        ]);

        $user->fill([
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }

        $user->save();

        return redirect()->route('school-portal.users.index')->with('status', 'Benutzer wurde aktualisiert.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $account = $this->requireAccount($request);
        $ownerAccount = $this->schoolOwnerAccount($account);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        SchoolRoom::create([
            'customer_portal_account_id' => $ownerAccount->id,
            'name' => $data['name'],
        ]);

        return redirect()->route('school-portal.dashboard')->with('status', 'Raum wurde angelegt.');
    }

    private function requireAccount(Request $request): CustomerPortalAccount
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $account = $id ? CustomerPortalAccount::find($id) : null;
        abort_unless($account, 403);

        return $account;
    }

    private function optionalAccount(Request $request): ?CustomerPortalAccount
    {
        $id = $request->session()->get(self::SESSION_KEY);

        return $id ? CustomerPortalAccount::find($id) : null;
    }

    private function schoolOwnerAccount(CustomerPortalAccount $account): CustomerPortalAccount
    {
        $query = CustomerPortalAccount::query()
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_SCHOOL);

        if ($account->dolibarr_thirdparty_id !== null) {
            $query->where('dolibarr_thirdparty_id', $account->dolibarr_thirdparty_id);
        } else {
            $query->where('company_name', $account->company_name);
        }

        return $query->orderBy('id')->first() ?? $account;
    }

    private function schoolAccountsQuery(CustomerPortalAccount $account): Builder
    {
        $query = CustomerPortalAccount::query()
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_SCHOOL);

        if ($account->dolibarr_thirdparty_id !== null) {
            $query->where('dolibarr_thirdparty_id', $account->dolibarr_thirdparty_id);
        } else {
            $query->where('company_name', $account->company_name);
        }

        return $query;
    }

    private function ensureMachineQrToken(CustomerMachine $machine): void
    {
        if (! blank($machine->qr_token)) {
            return;
        }

        $machine->forceFill(['qr_token' => $this->generateUniqueQrToken()])->save();
    }

    private function generateUniqueQrToken(): string
    {
        do {
            $token = Str::upper(Str::random(10));
        } while (CustomerMachine::where('qr_token', $token)->exists());

        return $token;
    }

    private function machineStatusLabel(CustomerMachine $machine): string
    {
        $ticket = $machine->latest_ticket ?? null;
        if (! $ticket) {
            return 'einsatzbereit';
        }

        return match ($ticket->status) {
            Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_INTERNALLY_DONE => 'Ticket offen',
            Ticket::STATUS_DONE, Ticket::STATUS_DELIVERED => 'einsatzbereit',
            default => 'unbekannt',
        };
    }

    private function machineStatusClass(CustomerMachine $machine): string
    {
        $ticket = $machine->latest_ticket ?? null;
        if (! $ticket) {
            return 'status-ok';
        }

        return match ($ticket->status) {
            Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_INTERNALLY_DONE => 'status-ticket',
            Ticket::STATUS_DONE, Ticket::STATUS_DELIVERED => 'status-ok',
            default => 'status-unknown',
        };
    }
}