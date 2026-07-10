<?php

namespace App\Http\Controllers;

use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalRequest;
use App\Services\Dolibarr\DolibarrClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CustomerPortalRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $requests = CustomerPortalRequest::query()
            ->when(array_key_exists($status, CustomerPortalRequest::statusOptions()), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('company_name', 'like', '%'.$search.'%')
                        ->orWhere('contact_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('zip', 'like', '%'.$search.'%')
                        ->orWhere('customer_number_input', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('customer-portal-requests.index', [
            'requests' => $requests,
            'statuses' => CustomerPortalRequest::statusOptions(),
            'activeStatus' => $status,
            'search' => $search,
        ]);
    }

    public function show(Request $request, CustomerPortalRequest $customerPortalRequest, DolibarrClient $dolibarr): View
    {
        $lookup = trim((string) $request->query('lookup'));
        $lookup = $lookup !== '' ? $lookup : $customerPortalRequest->company_name;
        $matches = [];
        $lookupWarning = null;

        try {
            $matches = $dolibarr->searchCustomers($lookup, 25);
        } catch (Throwable $exception) {
            $lookupWarning = $exception->getMessage();
        }

        return view('customer-portal-requests.show', [
            'portalRequest' => $customerPortalRequest,
            'statuses' => CustomerPortalRequest::statusOptions(),
            'lookup' => $lookup,
            'matches' => $matches,
            'lookupWarning' => $lookupWarning,
        ]);
    }

    public function link(Request $request, CustomerPortalRequest $customerPortalRequest, DolibarrClient $dolibarr): RedirectResponse
    {
        $data = $request->validate([
            'dolibarr_thirdparty_id' => ['required', 'integer'],
            'review_note' => ['nullable', 'string'],
        ]);

        $customer = $dolibarr->getCustomer((int) $data['dolibarr_thirdparty_id']);
        $this->upsertAccount($customerPortalRequest, $customer);

        $customerPortalRequest->forceFill([
            'status' => CustomerPortalRequest::STATUS_LINKED,
            'matched_dolibarr_thirdparty_id' => $customer['id'],
            'matched_dolibarr_customer_code' => $customer['code_client'] ?? null,
            'matched_customer_name' => $customer['name'],
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => (string) ($request->getUser() ?: 'intern'),
            'reviewed_at' => now(),
        ])->save();

        return redirect()->route('customer-portal-requests.show', $customerPortalRequest)->with('status', 'Anfrage wurde mit dem Dolibarr-Kunden verknuepft und ein Portalzugang wurde erstellt/aktualisiert.');
    }

    public function createCustomer(Request $request, CustomerPortalRequest $customerPortalRequest, DolibarrClient $dolibarr): RedirectResponse
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string'],
        ]);

        $customer = $dolibarr->createCustomer([
            'name' => $customerPortalRequest->company_name,
            'email' => $customerPortalRequest->email,
            'phone' => $customerPortalRequest->phone,
            'address' => $customerPortalRequest->street,
            'zip' => $customerPortalRequest->zip,
            'town' => $customerPortalRequest->city,
        ]);

        $this->upsertAccount($customerPortalRequest, $customer);

        $customerPortalRequest->forceFill([
            'status' => CustomerPortalRequest::STATUS_LINKED,
            'matched_dolibarr_thirdparty_id' => $customer['id'],
            'matched_dolibarr_customer_code' => $customer['code_client'] ?? null,
            'matched_customer_name' => $customer['name'],
            'review_note' => $data['review_note'] ?? 'Neuer Kunde aus Kundenportal-Anfrage angelegt.',
            'reviewed_by' => (string) ($request->getUser() ?: 'intern'),
            'reviewed_at' => now(),
        ])->save();

        return redirect()->route('customer-portal-requests.show', $customerPortalRequest)->with('status', 'Dolibarr-Kunde wurde angelegt und ein Portalzugang wurde erstellt.');
    }

    public function updateStatus(Request $request, CustomerPortalRequest $customerPortalRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(CustomerPortalRequest::statusOptions()))],
            'review_note' => ['nullable', 'string'],
        ]);

        $customerPortalRequest->forceFill([
            'status' => $data['status'],
            'review_note' => $data['review_note'] ?? $customerPortalRequest->review_note,
            'reviewed_by' => (string) ($request->getUser() ?: 'intern'),
            'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'Status wurde aktualisiert.');
    }

    private function upsertAccount(CustomerPortalRequest $portalRequest, array $customer): CustomerPortalAccount
    {
        return CustomerPortalAccount::query()->updateOrCreate(
            ['email' => mb_strtolower($portalRequest->email)],
            [
                'dolibarr_thirdparty_id' => (int) $customer['id'],
                'dolibarr_customer_code' => $customer['code_client'] ?? null,
                'company_name' => $customer['name'] ?: $portalRequest->company_name,
                'contact_name' => $portalRequest->contact_name,
                'phone' => $portalRequest->phone,
                'portal_scope' => (int) ($customer['id'] ?? 0) === 9
                    ? CustomerPortalAccount::PORTAL_SCOPE_GEISER
                    : CustomerPortalAccount::PORTAL_SCOPE_DEFAULT,
                'is_active' => true,
            ]
        );
    }
}
