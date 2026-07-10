<?php

namespace App\Http\Controllers;

use App\Models\SparePartCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class SparePartCategoryController extends Controller
{
    public function index(): View
    {
        return view('spare-part-categories.index', [
            'categories' => SparePartCategory::query()->withCount('spareParts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:spare_part_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        SparePartCategory::query()->create($data);

        return back()->with('status', 'Kategorie gespeichert.');
    }

    public function destroy(SparePartCategory $sparePartCategory): RedirectResponse
    {
        if ($sparePartCategory->spareParts()->exists()) {
            return back()->with('warning', 'Kategorie wird noch von Ersatzteilen verwendet und kann nicht geloescht werden.');
        }

        $sparePartCategory->delete();

        return back()->with('status', 'Kategorie geloescht.');
    }

    public function listJson(): JsonResponse
    {
        $list = SparePartCategory::query()->orderBy('name')->get()->map(function ($c) {
            return ['id' => $c->id, 'name' => $c->name];
        });

        return response()->json($list);
    }
}
