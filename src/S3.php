<?php

namespace Kipchak\Driver\S3;

use Kipchak\Core\Interfaces;
use Psr\Container\ContainerInterface;
use Kipchak\Driver\Config\Config;

class S3 implements Interfaces\Driver
{
    protected static ContainerInterface $container;

    public static function initialise(ContainerInterface $container, ?string $configFileName = null): void
    {
        self::$container = $container;
        self::set($configFileName);
    }

    public static function set(?string $configFileName = null): void
    {
        $configFileName = $configFileName ?? 'kipchak.s3';
        $connections = Config::get($configFileName);
        foreach($connections as $name => $connectionDetails) {
            self::$container->set('drivers.s3.' . $name, function (ContainerInterface $c) use ($name, $connectionDetails): Client {
                $options = [
                    'accessKeyId' => $connectionDetails['access_key'],
                    'accessKeySecret' => $connectionDetails['secret_key'],
                    'region' => $connectionDetails['region'],
                    'endpoint' => $connectionDetails['endpoint']
                ];

                // Optional: path-style addressing (https://endpoint/bucket/key instead of
                // https://bucket.endpoint/key). Required by MinIO and recommended for
                // Ceph-backed providers like Hetzner Object Storage. Defaults to false.
                if (array_key_exists('path_style', $connectionDetails)) {
                    $options['pathStyleEndpoint'] = $connectionDetails['path_style'] ? 'true' : 'false';
                }

                // Optional: disable aws-chunked streaming uploads for providers that do
                // not support them (e.g. Google Cloud Storage in interoperability mode).
                // Defaults to true.
                if (array_key_exists('send_chunked_body', $connectionDetails)) {
                    $options['sendChunkedBody'] = $connectionDetails['send_chunked_body'] ? 'true' : 'false';
                }

                $client = new Client($options);

                // Optional: default multipart part size in megabytes for upload()
                // (power of 2, max 4096; AsyncAws default is 64). Individual
                // upload() calls can still override via their own PartSize option.
                if (array_key_exists('part_size', $connectionDetails)) {
                    $client->setDefaultPartSize((int) $connectionDetails['part_size']);
                }

                return $client;
            });
        }

    }

    public static function get(?string $configKey = null): Client
    {
        return self::$container->get('drivers.s3.' . $configKey ?? 'default');
    }

}