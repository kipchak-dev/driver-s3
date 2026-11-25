<?php

namespace Kipchak\Driver\S3\Tests;

use AsyncAws\Core\AbstractApi;
use Kipchak\Driver\S3\S3;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\RetryableHttpClient;

/**
 * The optional per-connection limits: timeout, max_duration and retries.
 *
 * S1 needs a local listener that accepts connections and never answers. A listening socket that is never accepted
 * from does that: the kernel completes the connection, and no reply is ever written.
 */
class RequestLimitsTest extends TestCase
{
    private const BASE = [
        'access_key' => 'test-access-key',
        'secret_key' => 'test-secret-key',
        'region' => 'eu-central-1',
        'path_style' => true,
    ];

    /** S2: with none of the three set, the driver passes no HTTP client, so AsyncAws builds its own default. */
    public function testNoSettingMeansNoHttpClientOfOurOwn(): void
    {
        $this->assertNull(S3::httpClient(self::BASE + ['endpoint' => 'https://example.invalid']));
        $this->assertSame(3, $this->maxRetries(S3::client(self::BASE + ['endpoint' => 'https://example.invalid'])),
            'the client keeps AsyncAws\'s own retrying client and its 3 retries');
    }

    /** S3: settings on one connection do not reach another. */
    public function testSettingsOnOneConnectionDoNotReachAnother(): void
    {
        $limited = S3::client(self::BASE + ['endpoint' => 'https://a.invalid', 'timeout' => 5, 'max_duration' => 5, 'retries' => 1]);
        $plain = S3::client(self::BASE + ['endpoint' => 'https://b.invalid']);
        $this->assertSame(1, $this->maxRetries($limited));
        $this->assertSame(3, $this->maxRetries($plain));
    }

    public function testInvalidValuesAreRefused(): void
    {
        foreach ([['timeout' => 0], ['max_duration' => -1], ['timeout' => '5'], ['retries' => -1], ['retries' => 1.5]] as $bad) {
            try {
                S3::httpClient(self::BASE + ['endpoint' => 'https://example.invalid'] + $bad);
                $this->fail('accepted ' . json_encode($bad));
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * S1: with timeout 2, max_duration 6 and retries 1, an upload to a listener that never answers makes its one
     * retry and gives up within max_duration plus 1 second (two idle timeouts of 2 s plus a wait of about 1 s).
     */
    #[Group('network')]
    public function testAnUploadToASilentStoreGivesUpWithinTheBound(): void
    {
        [$server, $endpoint] = $this->silentListener();
        $client = S3::client(self::BASE + ['endpoint' => $endpoint, 'timeout' => 2, 'max_duration' => 6, 'retries' => 1]);

        $started = microtime(true);
        try {
            $client->upload('invoices', 'test.pdf', 'body');
            $this->fail('the upload to a silent store succeeded');
        } catch (\Throwable) {
        }
        $elapsed = microtime(true) - $started;
        fclose($server);

        $this->assertLessThan(6 + 1, $elapsed, 'gave up within max_duration plus 1 second');
        $this->assertGreaterThan(4.0, $elapsed, 'made the retry it was allowed');
    }

    /**
     * max_duration bounds one request together with its retries: with timeout 2, max_duration 3 and retries 3, the
     * upload gives up within 3 seconds plus 1, although three retries would otherwise take about 15 seconds.
     */
    #[Group('network')]
    public function testMaxDurationBoundsARequestAndItsRetries(): void
    {
        [$server, $endpoint] = $this->silentListener();
        $client = S3::client(self::BASE + ['endpoint' => $endpoint, 'timeout' => 2, 'max_duration' => 3, 'retries' => 3]);

        $started = microtime(true);
        try {
            $client->upload('invoices', 'test.pdf', 'body');
            $this->fail('the upload to a silent store succeeded');
        } catch (\Throwable) {
        }
        $elapsed = microtime(true) - $started;
        fclose($server);

        $this->assertLessThan(3 + 1, $elapsed);
    }

    /**
     * S1's control: without the time settings (retries 0, so one attempt only), the same upload is still running at
     * the bound plus 5 seconds. default_socket_timeout is set to 12 seconds here so that the control ends.
     */
    #[Group('network')]
    public function testWithoutTheSettingsTheUploadIsStillRunningAfterTheBound(): void
    {
        [$server, $endpoint] = $this->silentListener();
        $previous = ini_set('default_socket_timeout', '12');
        $client = S3::client(self::BASE + ['endpoint' => $endpoint, 'retries' => 0]);

        $started = microtime(true);
        try {
            $client->upload('invoices', 'test.pdf', 'body');
        } catch (\Throwable) {
        }
        $elapsed = microtime(true) - $started;
        fclose($server);
        ini_set('default_socket_timeout', $previous === false ? '60' : $previous);

        $this->assertGreaterThan(6 + 5, $elapsed, 'still running at the bound plus 5 seconds');
    }

    /** @return array{0: resource, 1: string} */
    private function silentListener(): array
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertNotFalse($server, $error);
        return [$server, 'http://' . stream_socket_get_name($server, false)];
    }

    private function maxRetries(AbstractApi $client): int
    {
        $http = (new \ReflectionProperty(AbstractApi::class, 'httpClient'))->getValue($client);
        $this->assertInstanceOf(RetryableHttpClient::class, $http);
        return (new \ReflectionProperty(RetryableHttpClient::class, 'maxRetries'))->getValue($http);
    }
}
