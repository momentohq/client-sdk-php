<?php

declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\AccessControl\DisposableTokenScopes;
use Momento\Auth\AuthClient;
use Momento\Auth\CredentialProvider;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Config\Configurations;
use Momento\Config\IConfiguration;
use Momento\Config\ReadConcern;
use PHPUnit\Framework\TestCase;

/**
 * @covers AuthClient
 */
class AuthClientTest extends TestCase
{
    private IConfiguration $configuration;
    private ICredentialProvider $authProvider;
    private int $DEFAULT_TTL_SECONDS = 10;
    private CacheClient $client;
    private AuthClient $authClient;
    private string $TEST_CACHE_NAME;

    public function setUp(): void
    {
        $this->configuration = Configurations\Laptop::latest()->withReadConcern(ReadConcern::CONSISTENT);
        $this->authProvider = CredentialProvider::fromEnvironmentVariablesV2();
        $this->client = new CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $this->authClient = new AuthClient($this->configuration, $this->authProvider);
        $this->TEST_CACHE_NAME = uniqid('php-integration-tests-auth-');
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
        $this->authClient->close();
        $this->client->close();
    }

    public function testGenerateDisposableTokenGrantsTheRequestedScope()
    {
        $key = uniqid();
        $value = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->authClient->generateDisposableToken(
            DisposableTokenScopes::cacheReadOnly($this->TEST_CACHE_NAME),
            60
        );
        $this->assertNull($response->asError());
        $success = $response->asSuccess();
        $this->assertNotNull($success, "Expected a success but got: $response");
        $this->assertGreaterThan(time(), $success->expiresAt());

        $disposableClient = new CacheClient(
            $this->configuration,
            CredentialProvider::fromDisposableToken($success->authToken()),
            $this->DEFAULT_TTL_SECONDS
        );
        try {
            $response = $disposableClient->get($this->TEST_CACHE_NAME, $key);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
            $this->assertEquals($value, $response->asHit()->valueString());

            $response = $disposableClient->set($this->TEST_CACHE_NAME, $key, $value);
            $this->assertNotNull($response->asError(), "Expected error but got: $response");
            $this->assertEquals(MomentoErrorCode::PERMISSION_ERROR, $response->asError()->errorCode());
        } finally {
            $disposableClient->close();
        }
    }

    public function testGenerateDisposableTokenRejectsANonPositiveExpiry()
    {
        $response = $this->authClient->generateDisposableToken(
            DisposableTokenScopes::cacheReadOnly($this->TEST_CACHE_NAME),
            0
        );
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }
}
