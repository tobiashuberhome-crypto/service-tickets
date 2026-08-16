<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\TicketPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketPartController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->isDone()) {
            return back()->with('warning', 'Erledigte Tickets koennen nicht mehr bearbeitet werden.');
        }

        $data = $request->validate([
            'spare_part_id' => ['required', 'exists:spare_parts,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        $sparePart = SparePart::query()->active()->findOrFail($data['spare_part_id']);
        $this->addPartToTicket($ticket, $sparePart, (float) $data['quantity'], 'ticket_manual');

        return back()->with('status', 'Ersatzteil hinzugefuegt.');
    }

    public function storeManualLines(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->isDone()) {
            return back()->with('warning', 'Erledigte Tickets koennen nicht mehr bearbeitet werden.');
        }

        $data = $request->validate([
            'manual_lines' => ['required', 'array', 'min:1'],
            'manual_lines.*.part_ref' => ['required', 'string', 'max:120'],
            'manual_lines.*.label' => ['required', 'string', 'max:255'],
            'manual_lines.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'manual_lines.*.sales_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        DB::transaction(function () use ($ticket, $data): void {
            foreach ($data['manual_lines'] as $line) {
                $ticket->parts()->create([
                    'spare_part_id' => null,
                    'quantity' => (float) $line['quantity'],
                    'part_ref_snapshot' => trim((string) $line['part_ref']),
                    'label_snapshot' => trim((string) $line['label']),
                    'description_snapshot' => null,
                    'purchase_price_snapshot' => null,
                    'sales_price_snapshot' => (float) $line['sales_price'],
                    'vat_rate_snapshot' => 19,
                    'unit_snapshot' => 'Stk',
                    'stock_movement_id' => null,
                ]);
            }

            $ticket->forceFill(['sync_status' => Ticket::SYNC_PENDING])->save();
        });

        return back()->with('status', 'Manuelle Rechnungsposition(en) hinzugefuegt.');
    }

    public function scanStore(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->isDone()) {
            return back()->with('warning', 'Erledigte Tickets koennen nicht mehr bearbeitet werden.');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        $sparePart = $this->findPartByCode($data['code']);

        if (! $sparePart) {
            throw ValidationException::withMessages([
                'code' => 'Kein aktives Ersatzteil mit diesem Code gefunden.',
            ]);
        }

        $this->addPartToTicket($ticket, $sparePart, (float) $data['quantity'], 'ticket_scan', $data['code']);

        return back()->with('status', 'Ersatzteil per Code hinzugefuegt.');
    }

    public function destroy(Ticket $ticket, TicketPart $ticketPart): RedirectResponse
    {
        if ($ticketPart->ticket_id !== $ticket->id) {
            abort(404);
        }

        if ($ticket->isDone() || $ticketPart->dolibarr_order_line_id) {
            return back()->with('warning', 'Diese Position wurde bereits uebertragen und kann hier nicht mehr geloescht werden.');
        }

        DB::transaction(function () use ($ticket, $ticketPart): void {
            if ($ticketPart->sparePart) {
                $ticketPart->sparePart->adjustStock(
                    (float) $ticketPart->quantity,
                    'ticket_remove',
                    $ticket,
                    'Ticketposition entfernt',
                    $ticketPart->part_ref_snapshot
                );
            }

            $ticketPart->delete();
            $ticket->forceFill(['sync_status' => Ticket::SYNC_PENDING])->save();
        });

        return back()->with('status', 'Ersatzteil entfernt.');
    }

    private function addPartToTicket(Ticket $ticket, SparePart $sparePart, float $quantity, string $movementType, ?string $code = null): TicketPart
    {
        return DB::transaction(function () use ($ticket, $sparePart, $quantity, $movementType, $code): TicketPart {
            $movement = $sparePart->adjustStock(
                -1 * $quantity,
                $movementType,
                $ticket,
                'Ersatzteil fuer Ticket '.$ticket->ticket_number,
                $code ?? $sparePart->part_ref
            );

            $ticketPart = $ticket->parts()->create([
                'spare_part_id' => $sparePart->id,
                'quantity' => $quantity,
                'part_ref_snapshot' => $sparePart->part_ref,
                'label_snapshot' => $sparePart->label,
                'description_snapshot' => $sparePart->description,
                'purchase_price_snapshot' => $sparePart->purchase_price,
                'sales_price_snapshot' => $sparePart->sales_price,
                'vat_rate_snapshot' => $sparePart->vat_rate,
                'unit_snapshot' => $sparePart->unit,
                'stock_movement_id' => $movement->id,
            ]);

            $ticket->forceFill(['sync_status' => Ticket::SYNC_PENDING])->save();

            return $ticketPart;
        });
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

