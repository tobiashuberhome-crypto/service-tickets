# Datei: app\Services\Tickets\TicketPdfService.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Services\Tickets\TicketPdfService.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Services\Pdf\PdfDocumentService;
use Illuminate\Support\Facades\Log;
use Throwable;

class TicketPdfService
{
    public function __construct(private readonly PdfDocumentService $pdfDocuments)
    {
    }

    public function generateGeiserTicketPdf(Ticket $ticket): Ticket
    {
        $ticket->load(['customerMachine', 'customerMachineProfile', 'parts', 'serviceLines']);

        $result = $this->pdfDocuments->generateAndStore(
            'geiser-ticket',
            [
                'ticket' => $ticket,
                'estimateLines' => collect($ticket->customer_portal_estimate_lines ?? [])->all(),
            ],
            [
                'ticket_number' => $ticket->ticket_number,
            ]
        );

        $metadata = [
            'pdf_template_key' => $result['template'],
            'pdf_layout_key' => $result['layout'],
            'pdf_disk' => $result['disk'],
            'pdf_path' => $result['path'],
            'pdf_generated_at' => now(),
        ];

        $ticket->forceFill($metadata);

        try {
            $ticket->save();

            return $ticket->refresh();
        } catch (Throwable $exception) {
            Log::warning('PDF-Metadaten konnten nicht in tickets gespeichert werden.', [
                'ticket_id' => $ticket->id,
                'error' => $exception->getMessage(),
            ]);

            // Fallback: PDF existiert bereits auf Disk, daher in-memory Metadaten behalten
            // und Aufrufern den Download trotzdem ermoeglichen.
            return $ticket;
        }
    }
}

```
