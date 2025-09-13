<?php

return [
    'client_id' => env('ZATCA_CLIENT_ID'),
    'client_secret' => env('ZATCA_CLIENT_SECRET'),
    'device_uuid' => env('ZATCA_DEVICE_UUID'),
    'api_base' => env('ZATCA_API_BASE', 'https://sandbox.zatca.gov.sa/e-invoicing/'),
];
