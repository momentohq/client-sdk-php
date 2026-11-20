<?php

declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\CredentialProvider;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Config\Configurations;
use Momento\Config\IConfiguration;
use Momento\Topic\TopicClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers TopicClient
 */
class TopicClientTest extends TestCase
{
    private IConfiguration $configuration;
    private ICredentialProvider $authProvider;
    private int $DEFAULT_TTL_SECONDS = 10;
    private CacheClient $client;
    private TopicClient $topicClient;
    private string $TEST_CACHE_NAME;

    public function setUp(): void
    {
        $this->configuration = Configurations\Laptop::latest();
        $this->authProvider = CredentialProvider::fromEnvironmentVariablesV2();
        $this->client = new CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $this->topicClient = new TopicClient($this->configuration, $this->authProvider);
        $this->TEST_CACHE_NAME = uniqid('php-integration-tests-topic-');
        // Ensure test cache exists
        $createResponse = $this->client->createCache($this->TEST_CACHE_NAME);
        if ($createError = $createResponse->asError()) {
            throw $createError->innerException();
        }
    }

    public function tearDown(): void
    {
        $deleteResponse = $this->client->deleteCache($this->TEST_CACHE_NAME);
        if ($deleteError = $deleteResponse->asError()) {
            throw $deleteError->innerException();
        }
        $this->topicClient->close();
        $this->client->close();
    }

    public function testPublishAcceptsATextValue()
    {
        $response = $this->topicClient->publish($this->TEST_CACHE_NAME, uniqid(), "hello");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    public function testPublishBinaryAcceptsABinaryValue()
    {
        $response = $this->topicClient->publishBinary($this->TEST_CACHE_NAME, uniqid(), "\xff\x00\xfe");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    public function testPublishAsyncAcceptsSeveralValuesAtOnce()
    {
        $topicName = uniqid();
        $futures = [];
        for ($i = 0; $i < 5; $i++) {
            $futures[] = $this->topicClient->publishAsync($this->TEST_CACHE_NAME, $topicName, "hello $i");
        }
        foreach ($futures as $future) {
            $response = $future->wait();
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        }
    }

    public function testPublishRejectsAnEmptyTopicName()
    {
        $response = $this->topicClient->publish($this->TEST_CACHE_NAME, "", "hello");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }
}
