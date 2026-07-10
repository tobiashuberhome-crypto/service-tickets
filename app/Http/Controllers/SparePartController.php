<?php

namespace App\Http\Controllers;

use App\Models\MachineSparePartCompatibility;
use App\Models\SparePart;
use App\Models\SparePartCategory;
use App\Models\SparePartEan;
use App\Support\CompatibilityInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SparePartController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $spareParts = SparePart::query()
            ->with(['compatibilities', 'category', 'eans'])
            ->search($search)
            ->orderBy('part_ref')
            ->paginate(30)
            ->withQueryString();

        return view('spare-parts.index', [
            'spareParts' => $spareParts,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('spare-parts.create', [
            'sparePart' => new SparePart([
                'vat_rate' => 19,
                'unit' => 'Stk',
                'active' => true,
            ]),
            'compatibilityInput' => '',
            'eanInput' => '',
            'categories' => SparePartCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $machineIds = CompatibilityInput::parse($request->input('compatible_machine_ids'));
        $eanCodes = $this->parseEans($request->input('eans'));
        $this->ensureEansAvailable($eanCodes);

        $sparePart = SparePart::query()->create($data);
        $this->syncCompatibilities($sparePart, $machineIds);
        $this->syncEans($sparePart, $eanCodes);

        return redirect()->route('spare-parts.edit', $sparePart)->with('status', 'Ersatzteil gespeichert.');
    }

    public function edit(SparePart $sparePart): View
    {
        // Build compatibility input string with both IDs and Refs
        $compatibilityInput = $sparePart->compatibilities()
            ->orderBy('machine_product_id')
            ->get()
            ->map(function ($compat) {
                $parts = [];
                if ($compat->machine_product_id) {
                    $parts[] = $compat->machine_product_id;
                }
                if ($compat->machine_ref) {
                    $parts[] = $compat->machine_ref;
                }
                return implode(' / ', $parts);
            })
            ->implode("\n");

        return view('spare-parts.edit', [
            'sparePart' => $sparePart,
            'compatibilityInput' => $compatibilityInput,
            'eanInput' => $sparePart->eans()->orderBy('ean')->pluck('ean')->implode("\n"),
            'categories' => SparePartCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SparePart $sparePart): RedirectResponse
    {
        $data = $this->validatedData($request, $sparePart);
        $machineIds = CompatibilityInput::parse($request->input('compatible_machine_ids'));
        $eanCodes = $this->parseEans($request->input('eans'));
        $this->ensureEansAvailable($eanCodes, $sparePart);

        $sparePart->update($data);
        $this->syncCompatibilities($sparePart, $machineIds);
        $this->syncEans($sparePart, $eanCodes);

        return redirect()->route('spare-parts.edit', $sparePart)->with('status', 'Ersatzteil gespeichert.');
    }

    public function destroy(SparePart $sparePart): RedirectResponse
    {
        $sparePart->delete();

        return redirect()->route('spare-parts.index')->with('status', 'Ersatzteil geloescht.');
    }

    private function validatedData(Request $request, ?SparePart $sparePart = null): array
    {
        $ignoreId = $sparePart?->id ? ','.$sparePart->id : '';

        return $request->validate([
            'part_ref' => ['required', 'string', 'max:120', 'unique:spare_parts,part_ref'.$ignoreId],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:spare_part_categories,id'],
            'spare_part_type' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'supplier_ref' => ['nullable', 'string', 'max:255'],
            'manufacturer_part_number' => ['nullable', 'string', 'max:255'],
            'storage_location_1' => ['nullable', 'string', 'max:255'],
            'storage_location_2' => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sales_price' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'stock_quantity' => ['nullable', 'numeric', 'regex:/^-?\d+(\.\d{1,2})?$/'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'active' => ['nullable', 'boolean'],
        ]) + [
            'active' => $request->boolean('active'),
            'stock_quantity' => $request->input('stock_quantity', 0),
        ];
    }

    private function syncCompatibilities(SparePart $sparePart, array $machineIdentifiers): void
    {
        $entries = collect($machineIdentifiers)
            ->map(function (array $item): array {
                $id = isset($item['id']) && is_numeric($item['id']) ? (int) $item['id'] : null;
                $ref = isset($item['ref']) ? trim((string) $item['ref']) : null;

                return [
                    'id' => $id && $id > 0 ? $id : null,
                    'ref' => $ref !== '' ? $ref : null,
                ];
            })
            ->filter(fn (array $item): bool => $item['id'] !== null || $item['ref'] !== null)
            ->unique(fn (array $item): string => $item['id'] !== null ? 'id:'.$item['id'] : 'ref:'.mb_strtolower((string) $item['ref']))
            ->values();

        MachineSparePartCompatibility::query()
            ->where('spare_part_id', $sparePart->id)
            ->delete();

        foreach ($entries as $entry) {
            MachineSparePartCompatibility::query()->create([
                'spare_part_id' => $sparePart->id,
                'machine_product_id' => $entry['id'],
                'machine_ref' => $entry['ref'],
            ]);
        }
    }


    private function parseEans(?string $value): array
    {
        $items = preg_split('/[\r\n,;]+/', (string) $value) ?: [];

        return collect($items)
            ->map(fn (string $ean): string => preg_replace('/\s+/', '', trim($ean)) ?? '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function ensureEansAvailable(array $eanCodes, ?SparePart $sparePart = null): void
    {
        if ($eanCodes === []) {
            return;
        }

        $existing = SparePartEan::query()
            ->whereIn('ean', $eanCodes)
            ->when($sparePart, fn ($query) => $query->where('spare_part_id', '!=', $sparePart->id))
            ->pluck('ean')
            ->all();

        if ($existing !== []) {
            throw ValidationException::withMessages([
                'eans' => 'Diese EAN ist bereits einem anderen Ersatzteil zugeordnet: '.implode(', ', $existing),
            ]);
        }
    }

    private function syncEans(SparePart $sparePart, array $eanCodes): void
    {
        SparePartEan::query()
            ->where('spare_part_id', $sparePart->id)
            ->whereNotIn('ean', $eanCodes ?: ['__none__'])
            ->delete();

        foreach ($eanCodes as $eanCode) {
            SparePartEan::query()->updateOrCreate(
                ['ean' => $eanCode],
                ['spare_part_id' => $sparePart->id]
            );
        }
    }

    public function scanStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'in:increase,decrease'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        $sparePart = $this->findPartByCode($data['code']);

        if (! $sparePart) {
            throw ValidationException::withMessages([
                'code' => 'Kein aktives Ersatzteil mit diesem Code gefunden.',
            ]);
        }

        $quantity = (float) $data['quantity'];
        $signedQuantity = $data['direction'] === 'increase' ? $quantity : -1 * $quantity;

        $sparePart->adjustStock(
            $signedQuantity,
            $data['direction'] === 'increase' ? 'scan_increase' : 'scan_decrease',
            null,
            'Bestand per Code-Scan angepasst',
            $data['code']
        );

        return redirect()
            ->route('spare-parts.index')
            ->with('status', 'Bestand fuer '.$sparePart->part_ref.' angepasst.');
    }

    private function findPartByCode(string $code): ?SparePart
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        return SparePart::query()
            ->active()
            ->where(function (Builder $query) use ($code): void {
                $query->where('part_ref', $code)
                    ->orWhere('supplier_ref', $code)
                    ->orWhere('manufacturer_part_number', $code)
                    ->orWhereHas('eans', function (Builder $ean) use ($code): void {
                        $ean->where('ean', $code);
                    });
            })
            ->first();
    }
}
