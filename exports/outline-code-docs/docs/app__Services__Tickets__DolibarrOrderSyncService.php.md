# Datei: app\Services\Tickets\DolibarrOrderSyncService.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Services\Tickets\DolibarrOrderSyncService.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

namespace App\Services\Tickets;

use App\Models\ServiceDefault;
use App\Models\SyncLog;
use App\Models\Ticket;
use App\Models\TicketPart;
use App\Models\TicketServiceLine;
use App\Services\Dolibarr\DolibarrClient;
use RuntimeException;
use Throwable;

class DolibarrOrderSyncService
{
    public function __construct(private readonly DolibarrClient $dolibarr)
    {
    }

    public function ensureDraftOrder(Ticket $ticket): Ticket
    {
        if ($ticket->dolibarr_order_id) {
            return $ticket;
        }

        $ticket->loadMissing('customerMachine');

        $order = $this->dolibarr->createDraftOrder($ticket->dolibarr_customer_id, [
            'note_private' => implode("\n", array_filter([
                'Ticket '.$ticket->ticket_number,
                'Maschine: '.$ticket->customerMachine?->displayName(),
                $ticket->repair_enabled ? 'Fehlerbeschreibung: '.$ticket->error_description : null,
            ])),
        ]);

        $ticket->forceFill([
            'dolibarr_order_id' => $order['id'],
            'dolibarr_order_ref' => $order['ref'],
            'sync_status' => Ticket::SYNC_PENDING,
            'sync_message' => null,
        ])->save();

        $this->log($ticket, 'create_order', 'ok', 'Dolibarr-Auftrag '.$order['ref'].' angelegt.', $order);

        return $ticket->refresh();
    }

    public function prepareServiceLines(Ticket $ticket): void
    {
        if (! $ticket->service_enabled && ! $ticket->cleaning) {
            $ticket->serviceLines()
                ->whereNull('dolibarr_order_line_id')
                ->delete();

            return;
        }

        // Load default services. Always include active defaults; if cleaning flag is set,
        // also include cleaning-specific defaults (avoid duplicates).
        $defaults = ServiceDefault::query()
            ->where('active', true)
            ->orderBy('product_ref')
            ->get();

        if ($ticket->cleaning) {
            $cleaningDefaults = ServiceDefault::query()
                ->where('active', true)
                ->where(function ($q) {
                    $q->where('product_ref', 'like', 'CLEAN%')
                      ->orWhere('label', 'like', '%Reinigung%');
                })
                ->orderBy('product_ref')
                ->get();

            $defaults = $defaults->merge($cleaningDefaults)->unique('id')->values();
        }

        $defaults->each(function (ServiceDefault $default) use ($ticket): void {
                $product = $this->dolibarr->findProductByRef($default->product_ref);

                TicketServiceLine::query()->firstOrCreate(
                    [
                        'ticket_id' => $ticket->id,
                        'product_ref' => $default->product_ref,
                    ],
                    [
                        'service_default_id' => $default->id,
                        'label_snapshot' => $product['label'] ?? $default->label ?? $default->product_ref,
                        'quantity' => $default->quantity,
                        'sales_price_snapshot' => $product['price'] ?? null,
                        'vat_rate_snapshot' => $product['vat_rate'] ?? 19,
                    ]
                );
            });
    }

    public function complete(Ticket $ticket): Ticket
    {
        $ticket = $this->ensureDraftOrder($ticket)->load(['serviceLines', 'parts']);

        foreach ($ticket->serviceLines()->whereNull('dolibarr_order_line_id')->get() as $line) {
            $this->syncServiceLine($ticket, $line);
        }

        foreach ($ticket->parts()->whereNull('dolibarr_order_line_id')->get() as $line) {
            $this->syncPartLine($ticket, $line);
        }

        $ticket->forceFill([
            'status' => Ticket::STATUS_INTERNALLY_DONE,
            'sync_status' => Ticket::SYNC_SYNCED,
            'sync_message' => null,
            'completed_at' => now(),
        ])->save();

        $this->log($ticket, 'complete_ticket', 'ok', 'Ticket wurde nach Dolibarr uebertragen.');

        return $ticket->refresh();
    }

    public function activateOrder(Ticket $ticket): Ticket
    {
        if (! $ticket->dolibarr_order_id) {
            $ticket = $this->ensureDraftOrder($ticket);
        }

        $this->dolibarr->validateOrder((int) $ticket->dolibarr_order_id);

        $ticket->forceFill([
            'status' => Ticket::STATUS_IN_PROGRESS,
            'sync_status' => Ticket::SYNC_SYNCED,
            'sync_message' => null,
        ])->save();

        $this->log($ticket, 'activate_order', 'ok', 'Dolibarr-Auftrag '.$ticket->dolibarr_order_ref.' aktiviert.');

        return $ticket->refresh();
    }

    public function closeOrderAndCreateInvoice(Ticket $ticket): Ticket
    {
        $ticket = $this->ensureDraftOrder($ticket)->load(['serviceLines', 'parts']);

        $invoiceLines = [];

        foreach ($ticket->serviceLines as $line) {
            $product = $this->dolibarr->findProductByRef($line->product_ref);
            if (! $product) {
                continue;
            }

            $invoiceLines[] = [
                'fk_product' => (int) $product['id'],
                'qty' => (float) $line->quantity,
                'subprice' => (float) ($line->sales_price_snapshot ?? $product['price'] ?? 0),
                'tva_tx' => (float) ($line->vat_rate_snapshot ?? $product['vat_rate'] ?? 19),
                'desc' => $line->label_snapshot,
                'price_base_type' => 'HT',
                'product_type' => 1,
            ];
        }

        foreach ($ticket->parts as $line) {
            $description = trim(implode("\n", array_filter([
                $line->part_ref_snapshot.' - '.$line->label_snapshot,
                $line->description_snapshot,
            ])));

            $invoiceLines[] = [
                'desc' => $description,
                'qty' => (float) $line->quantity,
                'subprice' => (float) $line->sales_price_snapshot,
                'tva_tx' => (float) $line->vat_rate_snapshot,
                'price_base_type' => 'HT',
                'product_type' => 0,
            ];
        }

        $invoice = $this->dolibarr->createInvoiceFromOrder(
            (int) $ticket->dolibarr_order_id,
            (int) $ticket->dolibarr_customer_id,
            $invoiceLines
        );

        $ticket->forceFill([
            'dolibarr_invoice_id' => $invoice['id'],
            'dolibarr_invoice_ref' => $invoice['ref'],
            'status' => Ticket::STATUS_INTERNALLY_DONE,
            'sync_status' => Ticket::SYNC_SYNCED,
            'sync_message' => null,
            'completed_at' => now(),
        ])->save();

        $this->log($ticket, 'close_order_create_invoice', 'ok', 'Rechnung '.$invoice['ref'].' angelegt.', $invoice);

        return $ticket->refresh();
    }

    public function activateInvoice(Ticket $ticket): Ticket
    {
        if (! $ticket->dolibarr_invoice_id) {
            throw new \RuntimeException('Keine Dolibarr-Rechnung am Ticket hinterlegt. Bitte zuerst "intern erledigt" setzen.');
        }

        $this->dolibarr->validateInvoice((int) $ticket->dolibarr_invoice_id);

        $ticket->forceFill([
            'status' => Ticket::STATUS_DONE,
            'sync_status' => Ticket::SYNC_SYNCED,
            'sync_message' => null,
        ])->save();

        $this->log($ticket, 'activate_invoice', 'ok', 'Rechnung '.$ticket->dolibarr_invoice_ref.' aktiviert.');

        return $ticket->refresh();
    }

    public function try(callable $callback, Ticket $ticket, string $action): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            $ticket->markSyncError($exception->getMessage());
            $this->log($ticket, $action, 'error', $exception->getMessage());

            throw $exception;
        }
    }

    private function syncServiceLine(Ticket $ticket, TicketServiceLine $line): void
    {
        $product = $this->dolibarr->findProductByRef($line->product_ref);

        if (! $product) {
            throw new RuntimeException('Serviceleistung '.$line->product_ref.' wurde in Dolibarr nicht gefunden.');
        }

        $lineId = $this->dolibarr->addProductOrderLine((int) $ticket->dolibarr_order_id, [
            'product_id' => $product['id'],
            'quantity' => $line->quantity,
            'unit_price' => $line->sales_price_snapshot ?? $product['price'] ?? 0,
            'vat_rate' => $line->vat_rate_snapshot ?? $product['vat_rate'] ?? 19,
            'description' => $line->label_snapshot,
            'product_type' => 1,
        ]);

        $line->forceFill(['dolibarr_order_line_id' => $lineId])->save();
        $this->log($ticket, 'sync_service_line', 'ok', 'Serviceleistung '.$line->product_ref.' uebertragen.', ['line_id' => $lineId]);
    }

    private function syncPartLine(Ticket $ticket, TicketPart $line): void
    {
        $description = trim(implode("\n", array_filter([
            $line->part_ref_snapshot.' - '.$line->label_snapshot,
            $line->description_snapshot,
        ])));

        $lineId = $this->dolibarr->addFreeOrderLine((int) $ticket->dolibarr_order_id, [
            'description' => $description,
            'quantity' => $line->quantity,
            'unit_price' => $line->sales_price_snapshot,
            'vat_rate' => $line->vat_rate_snapshot,
        ]);

        $line->forceFill(['dolibarr_order_line_id' => $lineId])->save();
        $this->log($ticket, 'sync_part_line', 'ok', 'Ersatzteil '.$line->part_ref_snapshot.' uebertragen.', ['line_id' => $lineId]);
    }

    private function log(Ticket $ticket, string $action, string $status, ?string $message = null, ?array $payload = null): void
    {
        SyncLog::query()->create([
            'ticket_id' => $ticket->id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
        ]);
    }
}

```
