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
    // Optional time limits, for a store that may be slow or unreachable. Without them, a request has no time
    // limit and is retried 3 times. They apply to each HTTP request, not to a whole upload() or listing: a
    // multipart upload or a paged listing makes one request per part or page, each with its own limit.
    'invoices' => [
        'access_key' => env('INVOICES_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('INVOICES_SECRET_KEY', 'some-secret-key'),
        'region' => 'eu-central-1',
        'endpoint' => 'upcloudobjects.com',
        'timeout' => 5,       // seconds an attempt may wait for data
        'max_duration' => 12, // total seconds for one request, its retries included
        'retries' => 1,       // tries after the first; default 3
    ],
];
