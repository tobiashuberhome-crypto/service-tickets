# Datei: config\pdf_documents.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `config\pdf_documents.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

return [
    'defaults' => [
        'disk' => env('PDF_STORAGE_DISK', 'local'),
        'base_directory' => env('PDF_STORAGE_PATH', 'generated-pdfs'),
        'paper' => env('PDF_DEFAULT_PAPER', 'a4'),
        'orientation' => env('PDF_DEFAULT_ORIENTATION', 'portrait'),
        'layout' => env('PDF_DEFAULT_LAYOUT', 'default'),
    ],

    'layouts' => [
        'default' => [
            'company_name' => env('PDF_COMPANY_NAME', env('APP_NAME', 'Service Tickets')),
            'headline_color' => env('PDF_HEADLINE_COLOR', '#1d4ed8'),
            'muted_color' => env('PDF_MUTED_COLOR', '#667085'),
            'logo_url' => env('PDF_LOGO_URL'),
            'show_footer' => true,
            'footer_text' => env('PDF_FOOTER_TEXT', 'Automatisch generiertes Dokument'),
        ],
    ],

    'templates' => [
        'geiser-ticket' => [
            'view' => 'pdf.templates.geiser-ticket',
            'filename' => 'geiser-ticket-{ticket_number}.pdf',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'layout' => 'default',
        ],
            // Template for delivery notes (stored via PdfDocumentService)
            'tickets.delivery-note' => [
                'view' => 'tickets.delivery-note',
                'filename' => 'lieferschein-{generated_at}.pdf',
                'paper' => 'a4',
                'orientation' => 'portrait',
                'layout' => 'default',
            ],
        ],
];

```
