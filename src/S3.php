<?php

namespace Kipchak\Driver\S3;

use AsyncAws\Core\HttpClient\AwsRetryStrategy;
use Kipchak\Core\Interfaces;
use Psr\Container\ContainerInterface;
use Kipchak\Driver\Config\Config;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
            self::$container->set('drivers.s3.' . $name, function (ContainerInterface $c) use ($connectionDetails): Client {
                return self::client($connectionDetails);
            });
        }
    }

    /**
     * Builds the client for one connection from its settings.
     *
     * @param array<string, mixed> $connectionDetails
     */
    public static function client(array $connectionDetails): Client
    {
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

        $client = new Client($options, null, self::httpClient($connectionDetails));

        // Optional: default multipart part size in megabytes for upload()
        // (power of 2, max 4096; AsyncAws default is 64). Individual
        // upload() calls can still override via their own PartSize option.
        if (array_key_exists('part_size', $connectionDetails)) {
            $client->setDefaultPartSize((int) $connectionDetails['part_size']);
        }

        return $client;
    }

    /**
     * The HTTP client for one connection: null, so that AsyncAws builds its own default (no time limit, 3 retries),
     * unless the connection sets timeout, max_duration or retries.
     *
     * - timeout: seconds a request may wait for data before that attempt fails; each attempt has its own.
     * - max_duration: total seconds for one request, its retries included. Once it is reached, nothing is retried.
     * - retries: how many times a failed request is tried again, with waits of about 1, 2, 4 seconds and so on.
     *
     * All three apply to each HTTP request the client makes, not to an upload() or listing call as a whole: a
     * multipart upload or a paged listing makes one request per part or page, each with its own limit.
     *
     * @param array<string, mixed> $connectionDetails
     */
    public static function httpClient(array $connectionDetails): ?HttpClientInterface
    {
        $options = [];
        foreach (['timeout', 'max_duration'] as $key) {
            if (array_key_exists($key, $connectionDetails)) {
                $value = $connectionDetails[$key];
                if (!is_int($value) && !is_float($value) || $value <= 0) {
                    throw new \InvalidArgumentException($key . ' must be a number of seconds greater than 0');
                }
                $options[$key] = (float) $value;
            }
        }
        $retries = 3; // AsyncAws's own default
        if (array_key_exists('retries', $connectionDetails)) {
            if (!is_int($connectionDetails['retries']) || $connectionDetails['retries'] < 0) {
                throw new \InvalidArgumentException('retries must be a whole number, 0 or more');
            }
            $retries = $connectionDetails['retries'];
        } elseif ($options === []) {
            return null;
        }

        // The same retry strategy AsyncAws uses by default (async-aws/core AbstractApi): 1 s first wait, doubling,
        // 10% jitter, on the same status codes and on transport errors such as a timeout.
        return new RetryableHttpClient(
            HttpClient::create($options),
            new AwsRetryStrategy(AwsRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 1000, 2.0, 0, 0.1),
            $retries
        );
    }

    public static function get(?string $configKey = null): Client
    {
        return self::$container->get('drivers.s3.' . $configKey ?? 'default');
    }

}