<?php

namespace App\Http\Controllers;

use App\Models\CustomerMachineProfile;
use App\Models\CustomerPortalAccount;
use App\Models\Ticket;
use App\Services\Tickets\GeiserInvoiceCalculator;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Il-Coccolino-Tickets, die im Admin mit "THSS" markiert wurden, sind fuer Cibena tabu:
     * kein Dashboard/Historie/Maschinenprofil, kein Direktzugriff (canViewTicket), keine Aufnahme
     * in Cibena-Monatsrechnungen oder -Lieferscheine.
     *
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    protected function applyPortalVisibility(Builder $query): Builder
    {
        return $query->where('thss', false);
    }

    /**
     * Maschinenprofile, zu denen es ein THSS-Ticket gibt, sind fuer Cibena nicht abrufbar
     * (sonst wuerden Kontakt-/Maschinendaten solcher Maschinen ueber die Seriennummer-Suche sichtbar).
     *
     * @param Builder<CustomerMachineProfile> $query
     * @return Builder<CustomerMachineProfile>
     */
    protected function applyProfileVisibility(Builder $query): Builder
    {
        return $query->whereNotIn('id', Ticket::query()
            ->where('thss', true)
            ->whereNotNull('customer_machine_profile_id')
            ->select('customer_machine_profile_id'));
    }

    protected function supportsPriority(): bool
    {
        return false;
    }

    /**
     * Cibena bleibt auf die Statusschranke (nur "open" ist editierbar) beschraenkt - sowohl fuer
     * eigene Tickets als auch fuer die geteilten Il-Coccolino/Geiser-Tickets. Geiser selbst
     * erlaubt unbeschraenkte Bearbeitung unabhaengig vom Status (siehe canEditTicket() dort).
     */
    protected function canEditTicket(CustomerPortalAccount $account, Ticket $ticket): bool
    {
        return $this->canViewTicket($account, $ticket)
            && $ticket->status === Ticket::STATUS_OPEN;
    }

    public function monthlyInvoices(Request $request): \Illuminate\View\View
    {
        $account = $this->account($request);

        $invoices = \App\Models\MonthlyInvoice::query()
            ->where('portal_scope', static::PORTAL_SCOPE)
            ->where('dolibarr_customer_id', $account->dolibarr_thirdparty_id)
            ->whereHas('tickets', fn ($ticketQuery) => $this->applyPortalVisibility($ticketQuery))
            ->with(['tickets' => fn ($ticketRelation) => $this->applyPortalVisibility($ticketRelation->getQuery())])
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

    // THSS-Tickets duerfen niemals in einer Cibena-Rechnung auftauchen, auch nicht, wenn sie
    // erst nach Erstellung der Rechnung so gekennzeichnet wurden.
    $tickets = $this->applyPortalVisibility($monthlyInvoice->tickets()->getQuery())
        ->with(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines'])
        ->orderBy('acceptance_date')
        ->orderBy('ticket_number')
        ->get();

    abort_if($tickets->isEmpty(), 404);

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