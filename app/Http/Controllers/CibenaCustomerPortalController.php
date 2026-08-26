<?php

namespace App\Http\Controllers;

use App\Models\CustomerPortalAccount;
use App\Services\Tickets\GeiserInvoiceCalculator;
use Illuminate\Http\Request;

class CibenaCustomerPortalController extends GeiserCustomerPortalController
{
    protected const CUSTOMER_ID = 10;
    protected const SESSION_KEY = 'cibena_customer_portal_account_id';
    protected const PORTAL_SCOPE = CustomerPortalAccount::PORTAL_SCOPE_CIBENA;
    protected const PORTAL_ROUTE_PREFIX = 'cibena-portal';
    protected const VIEW_PREFIX = 'customer-portal-cibena';
    protected const PORTAL_NAME = 'Cibena-Serviceportal';

    /**
     * @return array<int>
     */
    protected function customerIdsForPortal(CustomerPortalAccount $account): array
    {
        return array_values(array_unique([
            9,
            10,
            (int) $account->dolibarr_thirdparty_id,
        ]));
    }

    public function monthlyInvoices(Request $request): \Illuminate\View\View
    {
        $account = $this->account($request);

        $invoices = \App\Models\MonthlyInvoice::query()
            ->where('portal_scope', static::PORTAL_SCOPE)
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->with('tickets')
            ->orderByDesc('invoice_year')
            ->orderByDesc('invoice_month')
            ->orderByDesc('sequence_number')
            ->paginate(20);

        return view('customer-portal-cibena.monthly-invoices', [
            'account' => $account,
            'invoices' => $invoices,
        ]);
    }
	public function downloadMonthlyInvoice(Request $request, \App\Models\MonthlyInvoice $monthlyInvoice, \App\Services\Dolibarr\DolibarrClient $dolibarr, GeiserInvoiceCalculator $invoiceCalculator)
{
    $account = $this->account($request);

    abort_unless(
        $monthlyInvoice->portal_scope === static::PORTAL_SCOPE
        && (int) $monthlyInvoice->dolibarr_customer_id === (int) $account->dolibarr_thirdparty_id,
        403
    );

    $tickets = $monthlyInvoice->tickets()
        ->with(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines'])
        ->orderBy('acceptance_date')
        ->orderBy('ticket_number')
        ->get();

    try {
        $invoiceRecipient = $dolibarr->getCustomer((int) $monthlyInvoice->dolibarr_customer_id);
    } catch (\Throwable $exception) {
        \Illuminate\Support\Facades\Log::warning('Monatsrechnung-Download: Dolibarr-Kunde konnte nicht geladen werden, verwende Ticket-Snapshot.', [
            'dolibarr_customer_id' => $monthlyInvoice->dolibarr_customer_id,
            'error' => $exception->getMessage(),
        ]);
        $invoiceRecipient = ['name' => $tickets->first()?->customer_name_snapshot];
    }

    $invoiceSummary = $invoiceCalculator->summarizeMany($tickets);

    $payload = array_merge($invoiceSummary, [
        'tickets' => $tickets,
        'createdAt' => $monthlyInvoice->generated_at ?? now(),
        'monthLabel' => $monthlyInvoice->invoice_label,
        'sender' => config('geiser_invoice.sender', []),
        'bank' => config('geiser_invoice.bank', []),
        'footerNote' => (string) config('geiser_invoice.footer_note', ''),
        'invoiceRecipient' => $invoiceRecipient,
        'monthlyInvoice' => $monthlyInvoice,
    ]);

    if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        return response()->view($this->portalView('monthly-invoice'), $payload);
    }

    $fileName = 'monatsrechnung-'.$monthlyInvoice->month_key.'-'.$monthlyInvoice->sequence_number.'.pdf';

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($this->portalView('monthly-invoice'), $payload)
        ->setPaper('a4', 'portrait');

    return $pdf->download($fileName);
	}
}