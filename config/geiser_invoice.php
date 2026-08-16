<?php

return [
    // Daten für Geiser-Rechnungen (Admin-PDF). Werte können bequem per .env geändert werden.
    'sender' => [
        'company_name' => env('GEISER_INVOICE_SENDER_COMPANY', 'Muster Service GmbH'),
        'address_line_1' => env('GEISER_INVOICE_SENDER_ADDRESS_1', 'Musterstraße 1'),
        'address_line_2' => env('GEISER_INVOICE_SENDER_ADDRESS_2', '12345 Musterstadt'),
        'email' => env('GEISER_INVOICE_SENDER_EMAIL', 'service@example.com'),
        'phone' => env('GEISER_INVOICE_SENDER_PHONE', '+49 0000 000000'),
        'tax_number' => env('GEISER_INVOICE_SENDER_TAX_NUMBER', ''),
    ],
    'bank' => [
        'account_holder' => env('GEISER_INVOICE_BANK_ACCOUNT_HOLDER', 'Muster Service GmbH'),
        'bank_name' => env('GEISER_INVOICE_BANK_NAME', 'Musterbank'),
        'iban' => env('GEISER_INVOICE_BANK_IBAN', 'DE00 0000 0000 0000 0000 00'),
        'bic' => env('GEISER_INVOICE_BANK_BIC', 'MUSTERBIC'),
        'payment_note' => env('GEISER_INVOICE_PAYMENT_NOTE', 'Bitte Rechnungsnummer GEI-{ticket} angeben.'),
    ],
    'footer_note' => env('GEISER_INVOICE_FOOTER_NOTE', ''),

    'mail' => [
        'to' => env('GEISER_INVOICE_MAIL_TO', ''),
        'from_address' => env('GEISER_INVOICE_MAIL_FROM_ADDRESS', 'service@example.com'),
        'from_name' => env('GEISER_INVOICE_MAIL_FROM_NAME', 'Service Tickets'),
        'subject' => env('GEISER_INVOICE_MAIL_SUBJECT', 'Rechnung - {serials}'),
        'body' => env('GEISER_INVOICE_MAIL_BODY', "Guten Tag,\n\nanbei erhalten Sie die Rechnung {invoice_number} fuer Ticket {ticket}.\nSeriennummer(n): {serials}\n\nViele Gruesse"),
    ],

    'work_report_mail' => [
        'from_address' => env('GEISER_WORK_REPORT_MAIL_FROM_ADDRESS', 'info@il-coccolino.de'),
        'from_name' => env('GEISER_WORK_REPORT_MAIL_FROM_NAME', 'Il Coccolino'),
    ],
];
