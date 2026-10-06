<?php

namespace App\Http\Controllers;

use App\Models\MonthlyInvoice;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class MonthlyInvoiceAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'portal_scope' => ['nullable', 'string', 'in:geiser,cibena,school,default,th_services'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
        ]);

        $query = MonthlyInvoice::query()
            ->with(['tickets'])
            ->orderByDesc('generated_at')
            ->orderByDesc('invoice_year')
            ->orderByDesc('invoice_month')
            ->orderByDesc('sequence_number');

        if (!empty($filters['portal_scope'])) {
            $query->where('portal_scope', $filters['portal_scope']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('dolibarr_customer_id', $filters['customer_id']);
        }

        if (!empty($filters['year'])) {
            $query->where('invoice_year', $filters['year']);
        }

        $invoices = $query->paginate(30)->withQueryString();

        return view('admin.monthly-invoices.index', [
            'invoices' => $invoices,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, MonthlyInvoice $monthlyInvoice): View
    {
        $monthlyInvoice->load(['tickets']);

        return view('admin.monthly-invoices.show', [
            'invoice' => $monthlyInvoice,
        ]);
    }
}
