<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfDocumentService
{
    public function generateAndStore(string $templateKey, array $data, array $filenameContext = [], array $overrides = []): array
    {
            // Defensive check: ensure DomPDF facade is available
            if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                throw new InvalidArgumentException('PDF-Generator (barryvdh/laravel-dompdf) ist nicht installiert. Führe composer require barryvdh/laravel-dompdf aus.');
            }

            $template = $this->resolveTemplate($templateKey);
        $layoutKey = (string) ($overrides['layout'] ?? $template['layout'] ?? config('pdf_documents.defaults.layout'));
        $layout = config('pdf_documents.layouts.'.$layoutKey, []);
        $disk = (string) ($overrides['disk'] ?? config('pdf_documents.defaults.disk', 'local'));
        $baseDirectory = trim((string) config('pdf_documents.defaults.base_directory', 'generated-pdfs'), '/');
        $paper = (string) ($overrides['paper'] ?? $template['paper'] ?? config('pdf_documents.defaults.paper', 'a4'));
        $orientation = (string) ($overrides['orientation'] ?? $template['orientation'] ?? config('pdf_documents.defaults.orientation', 'portrait'));

        $pdf = Pdf::loadView($template['view'], [
            ...$data,
            'pdfLayout' => $layout,
            'pdfTemplateKey' => $templateKey,
        ])->setPaper($paper, $orientation);

        $filename = $this->compileFilename((string) ($template['filename'] ?? 'document.pdf'), $filenameContext);
        $relativePath = trim($baseDirectory.'/'.$filename, '/');
        $filesystem = $this->filesystem($disk);

        $filesystem->put($relativePath, $pdf->output());

        return [
            'disk' => $disk,
            'path' => $relativePath,
            'template' => $templateKey,
            'layout' => $layoutKey,
            'filename' => $filename,
        ];
    }

    public function download(string $path, ?string $downloadName = null, ?string $disk = null): StreamedResponse
    {
        $storageDisk = $disk ?: (string) config('pdf_documents.defaults.disk', 'local');
        $filesystem = $this->filesystem($storageDisk);

        if (! $filesystem->exists($path)) {
            throw new InvalidArgumentException('PDF-Datei wurde nicht gefunden.');
        }

        $name = $downloadName ?: basename($path);

        return response()->streamDownload(
            static function () use ($filesystem, $path): void {
                $stream = $filesystem->readStream($path);
                if (! $stream) {
                    throw new InvalidArgumentException('PDF-Datei konnte nicht gelesen werden.');
                }

                fpassthru($stream);
                fclose($stream);
            },
            $name,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function resolveTemplate(string $templateKey): array
    {
            // allow dotted keys like 'tickets.delivery-note' -> convert to config key 'tickets.delivery-note'
            $template = config('pdf_documents.templates.'.$templateKey);
            // fallback: support view key by treating templateKey as view path
            if (! is_array($template) || ! isset($template['view'])) {
                // if not configured, assume templateKey corresponds to a view (dot notation)
                return ['view' => $templateKey, 'filename' => ($templateKey).'.pdf'];
            }

            return $template;
        }

    private function compileFilename(string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $safeValue = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $value) ?: 'n-a';
            $replacements['{'.$key.'}'] = trim($safeValue, '-');
        }

        $filename = strtr($template, $replacements);
        if (! str_ends_with(mb_strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return $filename;
    }

    private function filesystem(string $disk): Filesystem
    {
        return Storage::disk($disk);
    }
}
