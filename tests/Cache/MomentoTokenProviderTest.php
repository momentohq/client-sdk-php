<?php
declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Auth\StringMomentoTokenProvider;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\InvalidArgumentException;
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
    const AUTH_TOKEN_NAME = 'TEST_AUTH_TOKEN';

    public function setUp(): void
    {
        try {
            $this->authToken = getenv(self::AUTH_TOKEN_NAME);
        } catch (TypeError) {
            // getenv returned false
            throw new RuntimeException(
                "Integration tests require TEST_AUTH_TOKEN environment variable"
            );
        }
    }

    public function testEnvVarToken_HappyPath()
    {
        $authProvider = new EnvMomentoTokenProvider(self::AUTH_TOKEN_NAME);
        $this->assertEquals($this->authToken, $authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testStringToken_HappyPath()
    {
        $authProvider = new StringMomentoTokenProvider($this->authToken);
        $this->assertEquals($this->authToken, $authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testEnvVarToken_fromEnvVar_HappyPath()
    {
        $authProvider = EnvMomentoTokenProvider::fromEnvironmentVariable(self::AUTH_TOKEN_NAME);
        $this->assertEquals($this->authToken, $authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testStringToken_fromString_HappyPath()
    {
        $authProvider = StringMomentoTokenProvider::fromString($this->authToken);
        $this->assertEquals($this->authToken, $authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testEnvVarToken_ProxySetup_HappyPath()
    {
        $authProvider = new EnvMomentoTokenProvider(self::AUTH_TOKEN_NAME, "ctl", "cache", "ctlTrustedCert", "cacheTrustedCert");
        $this->assertEquals($this->authToken, $authProvider->getAuthToken());
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
                self::AUTH_TOKEN_NAME, $argsCopy[0], $argsCopy[1], $argsCopy[2], $argsCopy[3]
            );
        }
    }

}

