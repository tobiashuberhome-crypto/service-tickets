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

    'easyappointments' => [
        'webhook_token' => env('EASYAPPOINTMENTS_WEBHOOK_TOKEN'),
        'customer_id' => (int) env('EASYAPPOINTMENTS_CUSTOMER_ID', 0),
        'customer_name' => env('EASYAPPOINTMENTS_CUSTOMER_NAME', 'EasyAppointments'),
        'default_machine_ref' => env('EASYAPPOINTMENTS_DEFAULT_MACHINE_REF', 'Terminbuchung'),
    ],

    'internal_api_token' => env('INTERNAL_API_TOKEN'),
];
