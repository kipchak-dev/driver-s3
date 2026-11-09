# S3 Compatible Object Storage Driver for Kipchak

This driver is based on the Async AWS S3 Client (SimpleS3Client).

This is a standalone S3 package as opposed to the official AWS SDK, which includes the entire AWS feature set for all their services.

It supports connections to multiple S3 / object storage servers.

For more details, please see:

* https://async-aws.com/integration/simple-s3.html
* https://async-aws.com/clients/s3.html
* https://async-aws.com/configuration.html

#### Composer Package

`kipchak/driver-s3`

#### Sample Config File:

This config file should be placed in your Kipchak project's config directory, as in the examples
project at https://github.com/kipchak-dev/examples/blob/master/config/kipchak.s3.php.

The config file should look like this (also available in sample.config.php):

```
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
    'hetzner' => [
        'access_key' => env('HETZNER_ACCESS_KEY', 'some-access-key'),
        'secret_key' => env('HETZNER_SECRET_KEY', 'some-secret-key'),
        'region' => 'fsn1',
        'endpoint' => 'https://fsn1.your-objectstorage.com',
        'path_style' => true,
    ],
];

```

#### Optional connection settings

Every connection requires `access_key`, `secret_key`, `region` and `endpoint`. Three
further keys are optional, for S3-compatible providers that deviate from AWS behaviour
and for tuning uploads:

* **`path_style`** (bool, default `false`) — use path-style addressing
  (`https://endpoint/bucket/key`) instead of virtual-hosted addressing
  (`https://bucket.endpoint/key`). Required by MinIO and recommended for Ceph-backed
  providers such as Hetzner Object Storage, where per-bucket DNS may not exist.
* **`send_chunked_body`** (bool, default `true`) — whether uploads are streamed with
  aws-chunked content encoding. Disable for providers that do not support it (e.g.
  Google Cloud Storage in interoperability mode).
* **`part_size`** (int, default `64`) — megabytes per part for multipart `upload()`.
  Must be a power of 2, max 4096. S3 allows at most 10,000 parts per object, so the
  default supports objects up to 640GB; raise it (128, 256, 512…) for larger objects.
  Individual `upload()` calls can still override it via `['PartSize' => 128]`.

## How to use it?

Install it via composer: `composer require kipchak/driver-s3`.

### Example Usage

```php
// Initialise the driver in drivers/drivers.php
\Kipchak\Driver\S3\S3::initialise($container);

// Load the S3 driver.
$s3 = \Kipchak\Driver\S3\S3::get('default'); // default is the connection name in the config file.
// Use the $s3 client as you would the S3 client documented at https://async-aws.com/clients/s3.html.
```

## Detailed Documentation

https://kipchak.dev/docs/driver-object-storage

## What is a Kipchak Driver?

Kipchak Drivers are used to connect Kipchak to various data sources or storage systems.

They provide a standardized interface for interacting with different data sources, allowing developers to
focus on building their applications rather than dealing with the complexities of each data source.

Drivers were introduced as a part of the Kipchak 2.0 release.

Drivers are basically Container Dependencies injected into Kipchak's Service Container.

## How do Kipchak Drivers work?

Some Kipchak drivers are wired into the Service Container via a config file (where required).

If applicable, you will find a sample config file in this repository as well as in the starter project at https://1x.ax/mamluk/kipchak/starter.

Each driver defines an implementation of Kipchak's Driver Interface. Where applicable, the dependency may
accept a parameter for a specific instance of the driver specified as a config property, for instance, in the case
of multiple database connections or S3 buckets.

So you may access the driver by invoking \Kipchak\Driver\Memcached\Memcached::get('cache'), where
`cache` is the name of one of the memcached connection pools specified in the config file.
