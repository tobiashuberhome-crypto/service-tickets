<?php

return [
    // Daten für die "TH Services & Solutions"-Rechnungskopie (Kleinunternehmer gem. §19 UStG).
    // Werte können bequem per .env geändert werden.
    'sender' => [
        'company_name' => env('TH_SERVICES_INVOICE_SENDER_COMPANY', 'TH Services & Solutions'),
        'contact_name' => env('TH_SERVICES_INVOICE_SENDER_CONTACT', 'Tobias Huber'),
        'address_line_1' => env('TH_SERVICES_INVOICE_SENDER_ADDRESS_1', 'Dorfstraße 5a'),
        'address_line_2' => env('TH_SERVICES_INVOICE_SENDER_ADDRESS_2', '82287 Jesenwang'),
        'country' => env('TH_SERVICES_INVOICE_SENDER_COUNTRY', 'Deutschland'),
    ],
    'bank' => [
        'account_holder' => env('TH_SERVICES_INVOICE_BANK_ACCOUNT_HOLDER', 'Tobias Huber'),
        'bank_name' => env('TH_SERVICES_INVOICE_BANK_NAME', 'Raiffeisenbank Westkreis FFB e.G.'),
        'blz' => env('TH_SERVICES_INVOICE_BANK_BLZ', '70169460'),
        'account_number' => env('TH_SERVICES_INVOICE_BANK_ACCOUNT_NUMBER', '437018'),
        'iban' => env('TH_SERVICES_INVOICE_BANK_IBAN', 'DE49 7016 9460 0000 4370 18'),
        'bic' => env('TH_SERVICES_INVOICE_BANK_BIC', 'GENODEF1MOO'),
    ],

    // Pflichthinweis für Kleinunternehmer-Rechnungen gem. §19 UStG.
    'vat_note' => env(
        'TH_SERVICES_INVOICE_VAT_NOTE',
        'Kein Ausweis der Umsatzsteuer gemäß § 19 UStG (Kleinunternehmerregelung).'
    ),
];
