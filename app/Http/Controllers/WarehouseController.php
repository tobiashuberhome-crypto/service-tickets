<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\SparePartCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $manufacturer = trim((string) $request->query('manufacturer'));
        $type = trim((string) $request->query('type'));
        $categoryId = $request->query('category_id');
        $lowStock = $request->boolean('low_stock');
        $search = trim((string) $request->query('q'));

        $spareParts = SparePart::query()
            ->with(['category', 'eans'])
            ->search($search)
            ->when($manufacturer !== '', fn ($query) => $query->where('manufacturer', $manufacturer))
            ->when($type !== '', fn ($query) => $query->where('spare_part_type', $type))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($lowStock, function ($query): void {
                $query->whereNotNull('minimum_stock')
                    ->whereColumn('stock_quantity', '<=', 'minimum_stock');
            })
            ->orderBy('part_ref')
            ->paginate(50)
            ->withQueryString();

        return view('warehouse.index', [
            'spareParts' => $spareParts,
            'search' => $search,
            'manufacturer' => $manufacturer,
            'type' => $type,
            'categoryId' => $categoryId,
            'lowStock' => $lowStock,
            'manufacturers' => SparePart::query()->whereNotNull('manufacturer')->where('manufacturer', '!=', '')->distinct()->orderBy('manufacturer')->pluck('manufacturer'),
            'types' => SparePart::query()->whereNotNull('spare_part_type')->where('spare_part_type', '!=', '')->distinct()->orderBy('spare_part_type')->pluck('spare_part_type'),
            'categories' => SparePartCategory::query()->orderBy('name')->get(),
        ]);
    }
}
