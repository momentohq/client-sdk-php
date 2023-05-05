<?php

namespace Momento\Tests\Cache;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations;
use PHPUnit\Framework\TestCase;

class GetTimingTest extends TestCase
{
    private int $DEFAULT_TTL_SECONDS = 600;
    private CacheClient $client;
    private string $TEST_CACHE_NAME;

    public function setUp(): void
    {
        $configuration = Configurations\Laptop::latest();
        $authProvider = new EnvMomentoTokenProvider("TEST_AUTH_TOKEN");
        $this->client = new CacheClient($configuration, $authProvider, $this->DEFAULT_TTL_SECONDS);
        $this->TEST_CACHE_NAME = uniqid('php-integration-tests-');
        // Ensure test cache exists
        $createResponse = $this->client->createCache($this->TEST_CACHE_NAME);
        if ($createError = $createResponse->asError()) {
            throw $createError->innerException();
        }
    }

    public function tearDown() : void {
        $deleteResponse = $this->client->deleteCache($this->TEST_CACHE_NAME);
        if ($deleteError = $deleteResponse->asError()) {
            throw $deleteError->innerException();
        }
    }
    public function testGetTiming()
    {
        $cacheName = uniqid();
        $key = uniqid();
        $value = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->set($cacheName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals("$response", get_class($response));


        $times = array();
        for ($i = 0; $i < 10; $i++) {
            sleep(2);

            $startTime = hrtime(true);

            $responses = array();
            for ($j = 0; $j < 50; $j++) {
                $responses[] = $this->client->get($cacheName, $key);
            }

            foreach ($responses as $response) {
                $this->assertNull($response->asError());
                $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
                $response = $response->asHit();
                $this->assertEquals($response->valueString(), $value);
                $this->assertEquals("$response", get_class($response) . ": $value");
            }

            $elapsedTime = (hrtime(true) - $startTime) / 1e9;
            $times[] = $elapsedTime;
        }
        print "Synchronous times:";
        print_r($times);
        printf("Average synchronous time: %.9f seconds\n", array_sum($times) / count($times));

        $response = $this->client->deleteCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    public function testGetAsyncTiming()
    {
        $cacheName = uniqid();
        $key = uniqid();
        $value = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->set($cacheName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals("$response", get_class($response));


        $times = array();
        for ($i = 0; $i < 10; $i++) {
            sleep(2);

            $startTime = hrtime(true);

            $responseFutures = array();
            for ($j = 0; $j < 50; $j++) {
                $responseFutures[] = $this->client->getAsync($cacheName, $key);
            }

            foreach ($responseFutures as $responseFuture) {
                $response = $responseFuture();
                $this->assertNull($response->asError());
                $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
                $response = $response->asHit();
                $this->assertEquals($response->valueString(), $value);
                $this->assertEquals("$response", get_class($response) . ": $value");
            }

            $elapsedTime = (hrtime(true) - $startTime) / 1e9;
            $times[] = $elapsedTime;
        }
        print "Async times:";
        print_r($times);
        printf("Average async time: %.9f seconds\n", array_sum($times) / count($times));

        $response = $this->client->deleteCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }
}
