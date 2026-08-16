# Datei: config\services.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `config\services.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

return [
    'dolibarr' => [
        'base_url' => rtrim((string) env('DOLIBARR_BASE_URL', ''), '/'),
        'api_key' => env('DOLIBARR_API_KEY'),
        'timeout' => (int) env('DOLIBARR_TIMEOUT', 20),
    ],

    'nextcloud' => [
        'base_url' => rtrim((string) env('NEXTCLOUD_BASE_URL', ''), '/'),
    ],

    'basic_auth' => [
        'user' => env('APP_BASIC_AUTH_USER'),
        'password' => env('APP_BASIC_AUTH_PASSWORD'),
    ],
];

```
