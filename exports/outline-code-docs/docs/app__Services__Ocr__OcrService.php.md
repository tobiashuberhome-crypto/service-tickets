# Datei: app\Services\Ocr\OcrService.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Services\Ocr\OcrService.php`
- **Stand:** 2026-06-27 14:03:19
- **Typ:** php

## Code

```php
<?php

namespace App\Services\Ocr;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Exception;

class OcrService
{
    protected string $pythonScript;
    protected string $pythonExecutable;

    public function __construct()
    {
        $this->pythonScript = base_path('scripts/ocr_scan.py');
        $this->pythonExecutable = $this->findPythonExecutable();
    }

    /**
     * Scan an image file and extract text
     */
    public function scanImage(string $imagePath, array $languages = ['de', 'en']): array
    {
        try {
            if (!file_exists($imagePath)) {
                throw new Exception("Image file not found: {$imagePath}");
            }

            $languageStr = implode(',', $languages);
            $process = new Process([
                $this->pythonExecutable,
                $this->pythonScript,
                $imagePath,
                $languageStr
            ]);

            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception("OCR process failed: {$process->getErrorOutput()}");
            }

            $output = json_decode($process->getOutput(), true);

            if (!$output) {
                throw new Exception("Invalid OCR output format");
            }

            if ($output['status'] !== 'success') {
                throw new Exception($output['message'] ?? 'OCR scan failed');
            }

            return $output;
        } catch (Exception $e) {
            Log::error('OCR scanning failed', [
                'image' => $imagePath,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'OCR scan failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find Python executable path
     */
    protected function findPythonExecutable(): string
    {
        // Windows: try common Python installation paths
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                'python',
                'python3',
                'C:\\Python314\\python.exe',
                'C:\\Python313\\python.exe',
                'C:\\Python312\\python.exe',
            ];

            foreach ($candidates as $candidate) {
                $process = new Process([$candidate, '--version']);
                try {
                    $process->run();
                    if ($process->isSuccessful()) {
                        return $candidate;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            throw new Exception('Python executable not found. Please ensure Python 3 is installed and accessible.');
        }

        // Linux/Mac: use 'python3' or 'python'
        return shell_exec('which python3 >/dev/null 2>&1 && echo "python3" || echo "python"');
    }
}

```
