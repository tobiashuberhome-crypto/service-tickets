<?php

namespace App\Http\Controllers;

use App\Models\ServiceDefault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceDefaultController extends Controller
{
    public function index(): View
    {
        return view('service-defaults.index', [
            'defaults' => ServiceDefault::query()->orderBy('product_ref')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_ref' => ['required', 'string', 'max:120', 'unique:service_defaults,product_ref'],
            'label' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'active' => ['nullable', 'boolean'],
        ]);

        ServiceDefault::query()->create($data + [
            'active' => $request->boolean('active'),
        ]);

        return back()->with('status', 'Serviceleistung gespeichert.');
    }

    public function destroy(ServiceDefault $serviceDefault): RedirectResponse
    {
        $serviceDefault->delete();

        return back()->with('status', 'Serviceleistung geloescht.');
    }
}
