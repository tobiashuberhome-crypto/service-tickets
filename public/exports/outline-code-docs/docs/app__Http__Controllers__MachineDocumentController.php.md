# Datei: app\Http\Controllers\MachineDocumentController.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Controllers\MachineDocumentController.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Controllers;

use App\Models\MachineDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineDocumentController extends Controller
{
    public function index(): View
    {
        return view('machine-documents.index', [
            'documents' => MachineDocument::query()
                ->orderBy('machine_ref')
                ->orderBy('title')
                ->latest('id')
                ->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'machine_ref' => ['required', 'string', 'max:255'],
            'machine_product_id' => ['nullable', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);

        MachineDocument::query()->create($data + [
            'active' => $request->boolean('active'),
        ]);

        return back()->with('status', 'Dokumentlink gespeichert.');
    }

    public function destroy(MachineDocument $machineDocument): RedirectResponse
    {
        $machineDocument->delete();

        return back()->with('status', 'Dokumentlink geloescht.');
    }

    public function update(Request $request, MachineDocument $machineDocument): RedirectResponse
    {
        $data = $request->validate([
            'machine_ref' => ['required', 'string', 'max:255'],
            'machine_product_id' => ['nullable', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $machineDocument->update($data + [
            'active' => $request->boolean('active'),
        ]);

        return back()->with('status', 'Dokumentlink aktualisiert.');
    }
}

```
