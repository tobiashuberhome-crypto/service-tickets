<?php

namespace App\Http\Controllers;

use App\Services\Dolibarr\DolibarrClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DolibarrLookupController extends Controller
{
    public function __construct(private readonly DolibarrClient $dolibarr)
    {
    }

    public function customers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json($this->dolibarr->searchCustomers((string) $request->query('q', '')));
    }

    public function createCustomer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:40'],
            'town' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json($this->dolibarr->createCustomer($data));
    }

    public function manufacturers(): JsonResponse
    {
        return response()->json($this->dolibarr->listManufacturers());
    }

    public function machines(Request $request): JsonResponse
    {
        $request->validate([
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'ref' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json($this->dolibarr->searchMachineProducts(
            $request->query('manufacturer'),
            $request->query('ref')
        ));
    }

    public function createMachine(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'ref' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($this->dolibarr->createMachineProduct($data));
    }
}
