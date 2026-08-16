<?php

namespace App\Services\Ocr;

use Illuminate\Support\Facades\Log;

class OcrDataParser
{
    /**
     * Parse OCR output to structured Geiser portal fields.
     *
     * Designed for the "il coccolino" Reparaturauftrag form with labels:
     * Seriennummer, Fabrikat, Kundenname, Annahmedatum, Telefon, E-Mail,
     * Lieferzeit, Garantie, Zubehör, handwritten description, and
     * "Bis zu einem Rechnungsbetrag in Höhe von X EUR"
     */
    public function parseGeiserForm(string $ocrText): array
    {
        $lines = $this->splitLines($ocrText);

        $parsed = [
            'serial_number'   => $this->extractSerialNumber($ocrText, $lines),
            'manufacturer'    => $this->extractManufacturer($ocrText, $lines),
            'machine_type'    => $this->extractMachineType($ocrText, $lines),
            'contact_name'    => $this->extractContactName($ocrText, $lines),
            'phone'           => $this->extractPhone($ocrText, $lines),
            'work_hours'      => $this->extractWorkHours($ocrText, $lines),
            'max_costs'       => $this->extractMaxCosts($ocrText, $lines),
            'description'     => $this->extractDescription($ocrText, $lines),
            'warranty'        => $this->extractWarranty($ocrText),
            'accessories'     => $this->extractAccessories($ocrText),
        ];

        // Remove null/empty values
        return array_filter($parsed, fn($v) => $v !== null && $v !== '' && $v !== []);
    }

    protected function splitLines(string $text): array
    {
        return preg_split('/\r?\n/', $text);
    }

    /**
     * Find value after a label in OCR text.
     * OCR often outputs "Label Value" on the same line or next line.
     */
    protected function findAfterLabel(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1] ?? '');
                $value = preg_replace('/\s{2,}/', ' ', $value);
                $value = trim($value, " \t\n\r\0\x0B.:-");
                if ($value !== '' && strlen($value) < 80) {
                    return $value;
                }
            }
        }
        return null;
    }

    /**
     * Seriennummer - e.g. "6405 0079"
     */
    protected function extractSerialNumber(string $text, array $lines): ?string
    {
        // Table cell: "Seriennummer   6405 0079"
        $patterns = [
            '/seriennummer\s+([a-z0-9][\w\s\-]{2,20})/i',
            '/s\.?n\.?\s*[:\-]?\s*([a-z0-9][\w\s\-]{2,20})/i',
            '/serien\s*nr\.?\s*[:\-]?\s*([a-z0-9][\w\s\-]{2,20})/i',
        ];

        $result = $this->findAfterLabel($text, $patterns);
        if ($result) {
            // Clean up trailing words that aren't part of serial (stop at known labels)
            $result = preg_replace('/\s+(fabrikat|kundenname|annahme|telefon|e.?mail).*/i', '', $result);
            return trim($result);
        }
        return null;
    }

    /**
     * Fabrikat / Hersteller - e.g. "Record 730"
     * On this form the field is labeled "Fabrikat"
     */
    protected function extractManufacturer(string $text, array $lines): ?string
    {
        return $this->findAfterLabel($text, [
            '/fabrikat\s+([^\n\r]{2,40})/i',
            '/hersteller\s*[:\-]?\s*([^\n\r]{2,40})/i',
            '/marke\s*[:\-]?\s*([^\n\r]{2,40})/i',
        ]);
    }

    /**
     * Machine type / Modell - parsed from Fabrikat if it contains numbers
     * e.g. "Record 730" → machine_type = "Record 730"
     * We use the same field as Fabrikat; caller maps to machine_ref_snapshot
     */
    protected function extractMachineType(string $text, array $lines): ?string
    {
        // Same source as manufacturer on this form – but map separately if needed
        return $this->findAfterLabel($text, [
            '/modell\s*[:\-]?\s*([^\n\r]{2,40})/i',
            '/maschinentyp\s*[:\-]?\s*([^\n\r]{2,40})/i',
            '/typ\s*[:\-]?\s*([a-z0-9][^\n\r]{1,30})/i',
        ]);
    }

    /**
     * Kundenname - e.g. "Zipper, Liesel"
     */
    protected function extractContactName(string $text, array $lines): ?string
    {
        return $this->findAfterLabel($text, [
            '/kundenname\s+([^\n\r]{2,50})/i',
            '/kunde\s*[:\-]?\s*([^\n\r]{2,50})/i',
            '/name\s*[:\-]?\s*([^\n\r]{2,50})/i',
        ]);
    }

    /**
     * Telefon - e.g. "0170 - 486 1135"
     */
    protected function extractPhone(string $text, array $lines): ?string
    {
        return $this->findAfterLabel($text, [
            '/telefon\s+([0-9][0-9\s\-\/+]{6,25})/i',
            '/tel\.?\s*[:\-]?\s*([0-9][0-9\s\-\/+]{6,25})/i',
        ]);
    }

    /**
     * Work hours - quantity before "Arbeitseinheit" in the estimate table
     * e.g. "9  Arbeitseinheit"
     */
    protected function extractWorkHours(string $text, array $lines): ?int
    {
        // Look for a number directly before "Arbeitseinheit"
        $patterns = [
            '/(\d+)\s+arbeitseinheit/i',
            '/menge\s+(\d+)\s+arbeits/i',
            '/^(\d+)\s*\n?\s*arbeits/im',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $val = (int) $matches[1];
                if ($val > 0 && $val <= 99) {
                    return $val;
                }
            }
        }
        return null;
    }

    /**
     * Max costs / Genehmigungslimit
     * e.g. "Bis zu einem Rechnungsbetrag in Höhe von 250 EUR darf die Maschine..."
     * Also matches "250 EUR" patterns
     */
    protected function extractMaxCosts(string $text, array $lines): ?float
    {
        $patterns = [
            // Exact form sentence
            '/rechnungsbetrag[^0-9]{0,50}([0-9]+(?:[.,][0-9]{1,2})?)\s*(?:eur|€)/i',
            '/ohne\s+r.{0,3}cksprache[^0-9]{0,50}([0-9]+(?:[.,][0-9]{1,2})?)/i',
            '/h.{0,3}he\s+von\s+([0-9]+(?:[.,][0-9]{1,2})?)\s*(?:eur|€)/i',
            // Generic fallback
            '/max\.?\s*(?:kosten)?[:\s]*[€]?\s*([0-9]+(?:[.,][0-9]{1,2})?)/i',
            '/genehmigungslimit[:\s]*[€]?\s*([0-9]+(?:[.,][0-9]{1,2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $cost = str_replace(',', '.', $matches[1]);
                $cost = (float) $cost;
                if ($cost > 0 && $cost < 100000) {
                    return $cost;
                }
            }
        }
        return null;
    }

    /**
     * Fehlerbeschreibung - handwritten text in the estimate table area
     * On this form it appears as free text rows after the fixed estimate lines.
     * Strategy: look for text after all standard articles, before the cost-limit sentence.
     */
    protected function extractDescription(string $text, array $lines): ?string
    {
        // Try to find text between known fixed rows and the signature block
        // Fixed markers that end the estimate table
        $skipMarkers = [
            'arbeitseinheit', 'servicegebühr', 'servicegebuehr',
            'vde', 'verbrauchsmaterial', 'pauschale',
            'menge', 'artikel', 'einzelpreis', 'gesamt',
        ];

        $collected = [];
        $capturing = false;

        foreach ($lines as $line) {
            $lower = strtolower(trim($line));

            // Start capturing after estimate table rows
            if (!$capturing) {
                // A line with handwritten text that is not a label
                if (preg_match('/n.{0,3}ht|langsam|licht|led|kaputt|defekt|reparatur/i', $line)) {
                    $capturing = true;
                }
            }

            // Stop at the cost-limit sentence or signature block
            if (preg_match('/rechnungsbetrag|unterschrift|datum:|mwst|aufwand/i', $lower)) {
                break;
            }

            if ($capturing && strlen(trim($line)) > 4) {
                // Skip lines that are clearly labels or numbers only
                $isLabel = false;
                foreach ($skipMarkers as $marker) {
                    if (str_contains($lower, $marker)) {
                        $isLabel = true;
                        break;
                    }
                }
                if (!$isLabel) {
                    $collected[] = trim($line);
                }
            }
        }

        if (count($collected) > 0) {
            $desc = implode(' ', $collected);
            $desc = preg_replace('/\s{2,}/', ' ', $desc);
            if (strlen($desc) > 4 && strlen($desc) < 500) {
                return $desc;
            }
        }

        // Fallback: generic keyword patterns
        return $this->findAfterLabel($text, [
            '/fehlerbeschreibung\s*[:\-]?\s*([^\n\r]{5,300})/i',
            '/problem\s*[:\-]?\s*([^\n\r]{5,300})/i',
        ]);
    }

    /**
     * Garantie - "ja" or "nein" checkbox
     */
    protected function extractWarranty(string $text): ?bool
    {
        // Form has "□ ja  X nein" or "X ja  □ nein"
        if (preg_match('/garantie[^ja]{0,20}(ja)/i', $text)) {
            return true;
        }
        if (preg_match('/garantie[^nein]{0,20}(nein)/i', $text)) {
            return false;
        }
        return null;
    }

    /**
     * Accessories - detect which checkboxes are ticked (X)
     * Returns array of recognized items
     */
    protected function extractAccessories(string $text): ?array
    {
        $items = [
            'presser_foot'   => ['nähfuß', 'nahfus', 'nahfuss', 'naehfuss'],
            'bobbin_case'    => ['spulenkapsel'],
            'bobbin'         => ['unterfadenspule', 'unterfaden'],
            'power_cable'    => ['kabel'],
            'foot_pedal'     => ['fußanlasser', 'fussanlasser', 'anlasser'],
            'case'           => ['koffer'],
        ];

        $found = [];
        $lower = strtolower($text);

        foreach ($items as $key => $keywords) {
            foreach ($keywords as $kw) {
                // Look for X or checked mark near the keyword
                if (preg_match('/[xX✓]\s*' . preg_quote($kw, '/') . '|' . preg_quote($kw, '/') . '\s*[xX✓]/i', $lower)) {
                    $found[] = $key;
                    break;
                }
            }
        }

        return count($found) > 0 ? $found : null;
    }
}
