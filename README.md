# S3 Compatible Object Storage Driver for Kipchak

This driver is based on the Async AWS S3 Client (SimpleS3Client). 

This is a standalone S3 package as opposed to the offical AWS which includes the entire AWS featureet for all their services..

It supports connections to multiple s3 / object storage servers.

For more details, please see:

* https://async-aws.com/integration/simple-s3.html
* https://async-aws.com/clients/s3.html
* https://async-aws.com/configuration.html

#### Composer Package

`kipchak/driver-git`

#### Sample Config File:

This config file should be placed in your Kipchak project's config directory, as in the starter
project at https://1x.ax/mamluk/kipchak/starter/~files/master/config/kipchak.s3.php.

The config file should look like this (also avalable in sample.config.php):

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

];

```

## How to use it?

Install it via composer: `composer require kipchak/driver-s3`.

### Example Usage

```php
// Initialise the driver in drivers/drivers.php
\Mamluk\Kipchak\Driver\S3\S3::iniitalise($container);

// Load the Git driver.
$s3 = Kipchak\Driver\S3\S3::get('default'); // default is the connection name in the config file.
// Use the $s3 client as you would the S3 client documented at https://async-aws.com/clients/s3.html.
```

## Detailed Documentation

https://kipchak.dev/docs/driver-s3

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

So you may access the driver by invoking \Mamluk\Kipchak\Driver\Memcached\Memcached::get('cache'), where
`cache` is the name of one of the memcached connection pools specified in the config file.