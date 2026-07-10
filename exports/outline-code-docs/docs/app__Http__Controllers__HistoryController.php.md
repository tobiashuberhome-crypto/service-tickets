# Datei: app\Http\Controllers\HistoryController.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Controllers\HistoryController.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketPart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;

        $partsTurnover = TicketPart::query()
            ->select([
                'part_ref_snapshot',
                'label_snapshot',
                DB::raw('sum(quantity) as consumed_quantity'),
                DB::raw('count(*) as position_count'),
            ])
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('part_ref_snapshot', 'label_snapshot')
            ->orderByDesc('consumed_quantity')
            ->limit(100)
            ->get();

        $serviceMachines = $this->machineTypeStats(true, false, $from, $to);
        $repairMachines = $this->machineTypeStats(false, true, $from, $to);

        return view('history.index', [
            'partsTurnover' => $partsTurnover,
            'serviceMachines' => $serviceMachines,
            'repairMachines' => $repairMachines,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    }

    private function machineTypeStats(bool $service, bool $repair, ?Carbon $from, ?Carbon $to)
    {
        return Ticket::query()
            ->join('customer_machines', 'tickets.customer_machine_id', '=', 'customer_machines.id')
            ->select([
                DB::raw("coalesce(customer_machines.machine_ref_snapshot, 'Unbekannt') as machine_type"),
                DB::raw('count(*) as ticket_count'),
            ])
            ->when($service, fn ($query) => $query->where('tickets.service_enabled', true))
            ->when($repair, fn ($query) => $query->where('tickets.repair_enabled', true))
            ->when($from, fn ($query) => $query->where('tickets.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('tickets.created_at', '<=', $to))
            ->groupBy('customer_machines.machine_ref_snapshot')
            ->orderByDesc('ticket_count')
            ->limit(100)
            ->get();
    }
}

```
