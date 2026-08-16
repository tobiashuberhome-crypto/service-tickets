<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\TicketPart;
use App\Models\TicketServiceLine;
use Illuminate\Support\Collection;

class GeiserInvoiceCalculator
{
    public function summarize(Ticket $ticket): array
    {
        $ticket->loadMissing(['parts', 'serviceLines']);

        $invoiceLines = $ticket->serviceLines
            ->map(fn (TicketServiceLine $line): array => $this->mapServiceLine($line))
            ->concat($ticket->parts->map(fn (TicketPart $part): array => $this->mapPartLine($part)))
            ->map(fn (array $line): array => $this->calculateLineTotals($line))
            ->values();

        $totalOriginalNet = round((float) $invoiceLines->sum('line_gross'), 2);
        $totalDiscountAmount = round((float) $invoiceLines->sum('discount_amount_gross'), 2);
        $totalNet = round((float) $invoiceLines->sum('line_net_after_discount'), 2);
        $totalVat = round((float) $invoiceLines->sum('vat_amount'), 2);
        $totalGross = round((float) $invoiceLines->sum('line_gross_after_discount'), 2);

        return [
            'invoiceLines' => $invoiceLines,
            'totalOriginalNet' => $totalOriginalNet,
            'totalDiscountAmount' => $totalDiscountAmount,
            'totalNet' => $totalNet,
            'totalVat' => $totalVat,
            'totalGross' => $totalGross,
            'vatLabel' => $this->buildVatLabel($invoiceLines),
        ];
    }

    private function mapServiceLine(TicketServiceLine $line): array
    {
        $description = trim((string) $line->label_snapshot);
        $isNmService = strcasecmp(trim((string) $line->product_ref), 'NM-Service') === 0
            || strcasecmp($description, 'NM-Service') === 0;

        return [
            'type' => 'Leistung',
            'reference' => $line->product_ref ?: '-',
            'description' => $description !== '' ? $description : 'Serviceleistung',
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->sales_price_snapshot,
            'vat_rate' => $this->normalizeVatRate($line->vat_rate_snapshot),
            'discount_rate' => $isNmService ? 0.0 : 0.20,
        ];
    }

    private function mapPartLine(TicketPart $part): array
    {
        $partLabel = trim((string) $part->label_snapshot);
        $partRef = trim((string) $part->part_ref_snapshot);
        $description = trim($partRef !== '' ? $partRef.' - '.$partLabel : $partLabel);

        return [
            'type' => 'Ersatzteil',
            'reference' => $partRef !== '' ? $partRef : '-',
            'description' => $description !== '' ? $description : 'Ersatzteil',
            'quantity' => (float) $part->quantity,
            'unit_price' => (float) $part->sales_price_snapshot,
            'vat_rate' => $this->normalizeVatRate($part->vat_rate_snapshot),
            'discount_rate' => 0.20,
        ];
    }

    private function calculateLineTotals(array $line): array
    {
        $line['line_gross'] = round($line['quantity'] * $line['unit_price'], 2);
        $line['discount_amount_gross'] = round($line['line_gross'] * (float) $line['discount_rate'], 2);
        $line['line_gross_after_discount'] = round($line['line_gross'] - $line['discount_amount_gross'], 2);

        $vatFactor = 1 + (((float) $line['vat_rate']) / 100);
        $line['line_net_after_discount'] = round($line['line_gross_after_discount'] / $vatFactor, 2);
        $line['vat_amount'] = round($line['line_gross_after_discount'] - $line['line_net_after_discount'], 2);

        // Backward compatible aliases for existing views/controllers.
        $line['line_total'] = $line['line_gross'];
        $line['discount_amount'] = $line['discount_amount_gross'];
        $line['discounted_total'] = $line['line_gross_after_discount'];

        return $line;
    }

    public function withCopyTexts(Ticket $ticket, Collection $invoiceLines): Collection
    {
        return $invoiceLines->map(function (array $line) use ($ticket): array {
            $line['copy_text'] = $this->buildCopyText($ticket, $line['description']);

            return $line;
        })->values();
    }

    private function buildCopyText(Ticket $ticket, string $articleDescription): string
    {
        $manufacturer = trim((string) ($ticket->customerMachine?->manufacturer_snapshot ?: $ticket->customerMachineProfile?->manufacturer_snapshot));
        $machineRef = trim((string) ($ticket->customerMachine?->machine_ref_snapshot ?: $ticket->customerMachineProfile?->machine_ref_snapshot));
        $serialNumber = trim((string) ($ticket->customerMachine?->serial_number ?: $ticket->customerMachineProfile?->serial_number));

        $machineLabel = trim(($manufacturer !== '' ? $manufacturer : 'Hersteller unbekannt').($machineRef !== '' ? ' '.$machineRef : ''));
        $serialLabel = $serialNumber !== '' ? $serialNumber : 'Seriennummer unbekannt';
        $descriptionLabel = trim($articleDescription) !== '' ? trim($articleDescription) : 'Position ohne Bezeichnung';

        return $machineLabel.' - '.$serialLabel.' - '.$descriptionLabel;
    }

    private function buildVatLabel(Collection $invoiceLines): string
    {
        $vatRates = $invoiceLines
            ->pluck('vat_rate')
            ->map(fn (float $rate): float => round($rate, 2))
            ->unique()
            ->values();

        if ($vatRates->count() !== 1) {
            return 'MwSt.';
        }

        return 'MwSt. ('.number_format((float) $vatRates->first(), 2, ',', '.').' %)';
    }

    private function normalizeVatRate(mixed $vatRate): float
    {
        $rate = (float) $vatRate;

        return $rate > 0 ? $rate : 19.0;
    }
}
