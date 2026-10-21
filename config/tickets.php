<?php

return [
    // Kunde, auf den der Ticket-Board-Filter standardmaessig steht (Dolibarr-Kunden-ID, 9 = Il Coccolino).
    // Leer lassen = standardmaessig alle Kunden.
    'default_customer_id' => env('TICKET_DEFAULT_CUSTOMER_ID', 9),

    // Kalenderwochen aelter als diese Zahl Wochen sind im Board immer eingeklappt ...
    'collapse_weeks_after' => 2,

    // ... und aelter als diese Zahl Wochen werden ausgeblendet (per Checkbox einblendbar).
    'hide_weeks_after' => 4,
];
