# Datei: app\Support\CompatibilityInput.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Support\CompatibilityInput.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

namespace App\Support;

class CompatibilityInput
{
    /**
     * Parse input into an array of machine identifiers.
     * Handles both numeric IDs and string references (e.g. "MACH-001").
     * 
     * Input format:
     *   - One per line or comma/semicolon separated
     *   - Can mix IDs (123) and Refs (MACH-001)
     * 
     * Returns array of ['id' => int|null, 'ref' => string|null]
     */
    public static function parse(?string $value): array
    {
        $value = (string) $value;
        
        // Split by newlines, commas, semicolons
        $lines = preg_split('/[\r\n,;]+/', $value) ?: [];
        
        $results = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Supports "123 / MACH-001" (format from edit view), pure IDs and pure refs.
            if (preg_match('/^\s*(\d+)\s*\/\s*(.+?)\s*$/', $line, $matches) === 1) {
                $id = (int) $matches[1];
                $ref = trim($matches[2]);
                if ($id > 0) {
                    $results[] = ['id' => $id, 'ref' => $ref !== '' ? $ref : null];
                }
                continue;
            }

            if (is_numeric($line)) {
                $id = (int) $line;
                if ($id > 0) {
                    $results[] = ['id' => $id, 'ref' => null];
                }
                continue;
            }

            $results[] = ['id' => null, 'ref' => $line];
        }

        return collect($results)
            ->unique(fn (array $item) => $item['id'] ? 'id:'.$item['id'] : 'ref:'.mb_strtolower((string) $item['ref']))
            ->values()
            ->all();
    }
}

```
