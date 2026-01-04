<?php

namespace Kipchak\Driver\S3;

use Kipchak\Core\Interfaces;
use Psr\Container\ContainerInterface;
use Kipchak\Driver\Config\Config;
use AsyncAws\SimpleS3\SimpleS3Client;

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
            self::$container->set('drivers.s3.' . $name, function (ContainerInterface $c) use ($name, $connectionDetails): SimpleS3Client {
                $client = new SimpleS3Client([
                    'accessKeyId' => $connectionDetails['access_key'],
                    'accessKeySecret' => $connectionDetails['secret_key'],
                    'region' => $connectionDetails['region'],
                ]);

                return $client;
            });
        }

    }

    public static function get(?string $configKey = null): SimpleS3Client
    {
        return self::$container->get('drivers.s3.' . $configKey ?? 'default');
    }

}