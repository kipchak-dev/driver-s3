<?php

use function Kipchak\Core\env;

return [
    'default' => [
        'access_key' => env('FRANKFURT_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('FRANKFURT_SECRET_KEY', 'some-secret-key'),
        'region' => 'eu-central-1',
        'endpoint' => 'upcloudobjects.com',
    ],
    'asia' => [
        'access_key' => env('ASIA_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('ASIA_SECRET_KEY', 'some-secret-key'),
        'region' => 'ap-central-1',
        'endpoint' => 'upcloudobjects.com',
    ],
    // S3-compatible providers (MinIO, Ceph-backed storage like Hetzner) often
    // need path-style addressing; some (e.g. GCS interoperability mode) need
    // chunked uploads disabled. All three extra keys are optional.
    'hetzner' => [
        'access_key' => env('HETZNER_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('HETZNER_SECRET_KEY', 'some-secret-key'),
        'region' => 'fsn1',
        'endpoint' => 'https://fsn1.your-objectstorage.com',
        'path_style' => true,          // default: false (virtual-hosted addressing)
        // 'send_chunked_body' => false, // default: true
        // 'part_size' => 128,           // default: 64 (MB per multipart part; power of 2, max 4096)
    ],
];
