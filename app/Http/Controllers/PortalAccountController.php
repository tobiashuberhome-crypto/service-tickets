<?php

namespace App\Http\Controllers;

use App\Models\CustomerPortalAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PortalAccountController extends Controller
{
    public function index(Request $request): View
    {
        $scope = $request->query('scope', '');
        $search = trim((string) $request->query('q', ''));

        $accounts = CustomerPortalAccount::query()
            ->when($scope !== '', fn ($q) => $q->where('portal_scope', $scope))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($s) use ($search): void {
                    $s->where('company_name', 'like', '%'.$search.'%')
                      ->orWhere('email', 'like', '%'.$search.'%')
                      ->orWhere('contact_name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('portal_scope')
            ->orderBy('company_name')
            ->get();

        return view('portal-accounts.index', [
            'accounts' => $accounts,
            'activeScope' => $scope,
            'search' => $search,
            'scopes' => [
                CustomerPortalAccount::PORTAL_SCOPE_DEFAULT => 'Kunden-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_GEISER => 'Il Coccolino-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_CIBENA => 'Cibena-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_SCHOOL => 'Schul-Portal',
            ],
        ]);
    }

    public function create(): View
    {
        return view('portal-accounts.create', [
            'account' => new CustomerPortalAccount(),
            'scopes' => [
                CustomerPortalAccount::PORTAL_SCOPE_DEFAULT => 'Kunden-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_GEISER => 'Il Coccolino-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_CIBENA => 'Cibena-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_SCHOOL => 'Schul-Portal',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAccount($request);

        CustomerPortalAccount::create([
            'portal_scope' => $data['portal_scope'],
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'dolibarr_thirdparty_id' => (int) $data['dolibarr_thirdparty_id'],
            'dolibarr_customer_code' => $data['dolibarr_customer_code'] ?? null,
            'password' => $data['initial_password'] ? Hash::make($data['initial_password']) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('portal-accounts.index')
            ->with('status', 'Portal-Konto wurde angelegt.');
    }

    public function edit(CustomerPortalAccount $portalAccount): View
    {
        return view('portal-accounts.edit', [
            'account' => $portalAccount,
            'scopes' => [
                CustomerPortalAccount::PORTAL_SCOPE_DEFAULT => 'Kunden-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_GEISER => 'Il Coccolino-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_CIBENA => 'Cibena-Portal',
                CustomerPortalAccount::PORTAL_SCOPE_SCHOOL => 'Schul-Portal',
            ],
        ]);
    }

    public function update(Request $request, CustomerPortalAccount $portalAccount): RedirectResponse
    {
        $data = $this->validateAccount($request, $portalAccount->id);

        $portalAccount->fill([
            'portal_scope' => $data['portal_scope'],
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'dolibarr_thirdparty_id' => (int) $data['dolibarr_thirdparty_id'],
            'dolibarr_customer_code' => $data['dolibarr_customer_code'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (!empty($data['initial_password'])) {
            $portalAccount->password = Hash::make($data['initial_password']);
        }

        $portalAccount->save();

        return redirect()
            ->route('portal-accounts.index')
            ->with('status', 'Portal-Konto aktualisiert.');
    }

    public function destroy(CustomerPortalAccount $portalAccount): RedirectResponse
    {
        $portalAccount->delete();

        return redirect()
            ->route('portal-accounts.index')
            ->with('status', 'Portal-Konto wurde gelöscht.');
    }

    private function validateAccount(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'portal_scope' => ['required', 'in:default,geiser,cibena,school'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customer_portal_accounts,email'.($ignoreId ? ','.$ignoreId : '')],
            'phone' => ['nullable', 'string', 'max:50'],
            'dolibarr_thirdparty_id' => ['required', 'integer', 'min:1'],
            'dolibarr_customer_code' => ['nullable', 'string', 'max:50'],
            'initial_password' => ['nullable', 'string', 'min:8', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
