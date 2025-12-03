<?php

declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Auth\StringMomentoTokenProvider;
use Momento\Auth\GlobalKeyEnvMomentoTokenProvider;
use Momento\Auth\GlobalKeyStringMomentoTokenProvider;
use Momento\Cache\Errors\InvalidArgumentError;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \Momento\Auth\EnvMomentoTokenProvider
 * @covers \Momento\Auth\StringMomentoTokenProvider
 */
class MomentoTokenProviderTest extends TestCase
{

    private string $authToken;
    const AUTH_TOKEN_NAME = 'MOMENTO_API_KEY';
    const GLOBAL_KEY_ENV_VAR_NAME = 'MOMENTO_TEST_GLOBAL_API_KEY';
    const TEST_GLOBAL_KEY_ENDPOINT = 'testEndpoint';
    const TEST_GLOBAL_API_KEY = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJ0IjoiZyIsImNwIjoiY29udHJvbC50ZXN0LmNvbSIsImMiOiJjYWNoZS50ZXN0LmNvbSJ9.T3ylW7iWLobcCsYQx92oImV2KgyBWmtFO-37uzw3qspSb18itIEH9zN49QFEm6joeIer_kXJ5R28ruF_JbUniA';
    const TEST_V1_API_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJPbmxpbmUgSldUIEJ1aWxkZXIiLCJpYXQiOjE2NzgzMDU4MTIsImV4cCI6NDg2NTUxNTQxMiwiYXVkIjoiIiwic3ViIjoianJvY2tldEBleGFtcGxlLmNvbSJ9.8Iy8q84Lsr-D3YCo_HP4d-xjHdT8UCIuvAYcxhFMyz8';
    const TEST_PRE_V1_API_KEY = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ1c2VyQHRlc3QuY29tIiwiY3AiOiJjb250cm9sLnRlc3QuY29tIiwiYyI6ImNhY2hlLnRlc3QuY29tIn0.c0Z8Ipetl6raCNHSHs7Mpq3qtWkFy4aLvGhIFR4CoR0OnBdGbdjN-4E58bAabrSGhRA8-B2PHzgDd4JF4clAzg';

    public function setUp(): void
    {
        if (!isset($_SERVER[self::AUTH_TOKEN_NAME])) {
            throw new RuntimeException(
                sprintf("Integration tests require %s environment variable", self::AUTH_TOKEN_NAME)
            );
        }

        $this->authToken = $_SERVER[self::AUTH_TOKEN_NAME];
    }

    public function testEnvVarToken_HappyPath()
    {
        $authProvider = new EnvMomentoTokenProvider(self::AUTH_TOKEN_NAME);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testStringToken_HappyPath()
    {
        $authProvider = new StringMomentoTokenProvider($this->authToken);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testStringToken_GlobalApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new StringMomentoTokenProvider(self::TEST_GLOBAL_API_KEY);
    }

    public function testEnvVarToken_fromEnvVar_HappyPath()
    {
        $authProvider = EnvMomentoTokenProvider::fromEnvironmentVariable(self::AUTH_TOKEN_NAME);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testStringToken_fromString_HappyPath()
    {
        $authProvider = StringMomentoTokenProvider::fromString($this->authToken);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testEnvVarToken_ProxySetup_HappyPath()
    {
        $authProvider = new EnvMomentoTokenProvider(self::AUTH_TOKEN_NAME, "ctl", "cache", "ctlTrustedCert", "cacheTrustedCert");
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertEquals("ctl", $authProvider->getControlEndpoint());
        $this->assertEquals("cache", $authProvider->getCacheEndpoint());
        $this->assertEquals("ctlTrustedCert", $authProvider->getTrustedControlEndpointCertificateName());
        $this->assertEquals("cacheTrustedCert", $authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testEnvVarToken_ProxySetup_MissingArg()
    {
        $args = ["ctl", "cache", "ctlTrustedCert", "cacheTrustedCert"];
        for ($i = 0; $i < count($args); $i++) {
            $argsCopy = $args;
            $argsCopy[$i] = null;
            $this->expectException(InvalidArgumentError::class);
            $authProvider = new EnvMomentoTokenProvider(
                self::AUTH_TOKEN_NAME,
                ...$argsCopy
            );
        }
    }

    public function testEnvVarToken_GlobalApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoTokenProvider(self::TEST_GLOBAL_API_KEY);
    }

    public function globalKeyTestEnvVarToken_EnvVarNotSet()
    {
        $authProvider = new GlobalKeyEnvMomentoTokenProvider(self::GLOBAL_KEY_ENV_VAR_NAME, self::TEST_GLOBAL_KEY_ENDPOINT);
        $this->expectException(InvalidArgumentError::class);
    }

    public function globalKeyTestEnvVarToken_fromEnvVar_EnvVarNotSet()
    {
        $authProvider = GlobalKeyEnvMomentoTokenProvider::globalKeyFromEnvironmentVariable(self::GLOBAL_KEY_ENV_VAR_NAME, self::TEST_GLOBAL_KEY_ENDPOINT);
        $this->expectException(InvalidArgumentError::class);
    }

    public function globalKeyTestEnvVarToken_EnvVarNameEmpty()
    {
        $authProvider = new GlobalKeyEnvMomentoTokenProvider('', self::TEST_GLOBAL_KEY_ENDPOINT);
        $this->expectException(InvalidArgumentError::class);
    }

    public function globalKeyTestEnvVarToken_EndpointEmpty()
    {
        $authProvider = new GlobalKeyEnvMomentoTokenProvider(self::GLOBAL_KEY_ENV_VAR_NAME, '');
        $this->expectException(InvalidArgumentError::class);
    }

    public function globalKeyTestStringToken_HappyPath()
    {
        $authProvider = new GlobalKeyStringMomentoTokenProvider(self::TEST_GLOBAL_API_KEY, self::TEST_GLOBAL_KEY_ENDPOINT);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
    }

    public function globalKeyTestStringToken_fromString_HappyPath()
    {
        $authProvider = GlobalKeyStringMomentoTokenProvider::globalKeyFromString(self::TEST_GLOBAL_API_KEY, self::TEST_GLOBAL_KEY_ENDPOINT);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
    }

    public function globalKeyTestStringToken_KeyEmpty()
    {
        $authProvider = new GlobalKeyStringMomentoTokenProvider('', self::TEST_GLOBAL_KEY_ENDPOINT);
        $this->expectException(InvalidArgumentError::class);
    }

    public function globalKeyTestStringToken_EndpointEmpty()
    {
        $authProvider = new GlobalKeyStringMomentoTokenProvider(self::TEST_GLOBAL_API_KEY, '');
        $this->expectException(InvalidArgumentError::class);
    }

    public function globalKeyTestStringToken_V1ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new StringMomentoTokenProvider(self::TEST_V1_API_KEY);
    }

    public function globalKeyTestStringToken_PreV1ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new StringMomentoTokenProvider(self::TEST_PRE_V1_API_KEY);
    }
}
