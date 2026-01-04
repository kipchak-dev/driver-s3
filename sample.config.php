<?php

use function Kipchak\Core\env;

return [
    'default' => [
        'access_key' => env('FRANKFURT_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('FRANKFURT_SECRET_KEY', 'some-secret-key'),
        'region' => 'eu-central-1',
    ],
    'asia' => [
        'access_key' => env('ASIA_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('ASIA_SECRET_KEY', 'some-secret-key'),
        'region' => 'ap-central-1',
    ],

];
