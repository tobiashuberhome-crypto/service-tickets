<?php

namespace App\Http\Controllers;

use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalRequest;
use App\Models\InternesTicket;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TodayController extends Controller
{
    private const LIST_LIMIT = 8;
    private const DUE_WITHIN_DAYS = 3;

    public function index(): View
    {
        $openStatuses = [
            Ticket::STATUS_AT_CG,
            Ticket::STATUS_HANDED_TO_TH,
            Ticket::STATUS_OPEN,
            Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_INTERNALLY_DONE,
        ];

        $with = ['customerMachine'];

        $dueQuery = Ticket::query()
            ->whereIn('status', $openStatuses)
            ->whereNotNull('target_date')
            ->whereDate('target_date', '<=', Carbon::today()->addDays(self::DUE_WITHIN_DAYS));

        // Fertige Tickets der Portale, die ueber Monatsrechnungen abgerechnet werden,
        // aber noch auf keiner Monatsrechnung stehen. THSS-Tickets haben eine eigene
        // Rechnung und werden hier bewusst nicht gezaehlt.
        $uninvoicedQuery = Ticket::query()
            ->whereIn('status', [Ticket::STATUS_DONE, Ticket::STATUS_DELIVERED])
            ->where('thss', false)
            ->whereHas('customerPortalAccount', fn ($query) => $query->whereIn('portal_scope', [
                CustomerPortalAccount::PORTAL_SCOPE_GEISER,
                CustomerPortalAccount::PORTAL_SCOPE_CIBENA,
            ]))
            ->doesntHave('monthlyInvoices');

        $syncErrorQuery = Ticket::query()->where('sync_status', Ticket::SYNC_ERROR);

        $requestQuery = CustomerPortalRequest::query()
            ->whereIn('status', [CustomerPortalRequest::STATUS_NEW, CustomerPortalRequest::STATUS_QUESTION]);

        return view('heute.index', [
            'counts' => [
                'open' => Ticket::query()->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])->count(),
                'handed' => Ticket::query()->whereIn('status', [Ticket::STATUS_AT_CG, Ticket::STATUS_HANDED_TO_TH])->count(),
                'due' => (clone $dueQuery)->count(),
                'uninvoiced' => (clone $uninvoicedQuery)->count(),
                'syncErrors' => (clone $syncErrorQuery)->count(),
                'requests' => (clone $requestQuery)->count(),
                'internal' => InternesTicket::query()->where('status', '!=', 'erledigt')->count(),
            ],
            'dueTickets' => (clone $dueQuery)->with($with)->orderBy('target_date')->limit(self::LIST_LIMIT)->get(),
            'uninvoicedTickets' => (clone $uninvoicedQuery)->with($with)->orderBy('acceptance_date')->limit(self::LIST_LIMIT)->get(),
            'syncErrorTickets' => (clone $syncErrorQuery)->with($with)->latest('updated_at')->limit(self::LIST_LIMIT)->get(),
            'newRequests' => (clone $requestQuery)->latest()->limit(self::LIST_LIMIT)->get(),
            'listLimit' => self::LIST_LIMIT,
            'today' => Carbon::today(),
        ]);
    }
}
