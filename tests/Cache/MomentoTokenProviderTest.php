<?php

declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Auth\StringMomentoTokenProvider;
use Momento\Auth\EnvMomentoV2TokenProvider;
use Momento\Auth\ApiKeyV2TokenProvider;
use Momento\Auth\DisposableTokenProvider;
use Momento\Cache\Errors\InvalidArgumentError;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \Momento\Auth\EnvMomentoTokenProvider
 * @covers \Momento\Auth\StringMomentoTokenProvider
 * @covers \Momento\Auth\EnvMomentoV2TokenProvider
 * @covers \Momento\Auth\ApiKeyV2TokenProvider
 * @covers \Momento\Auth\DisposableTokenProvider
 */
class MomentoTokenProviderTest extends TestCase
{

    private string $v1AuthToken;
    const AUTH_TOKEN_NAME = 'MOMENTO_API_KEY';
    const V2_API_KEY_ENV_VAR = 'MOMENTO_V2_API_KEY'; // cannot use default name in testing env var v2 method
    const TEST_ENDPOINT = 'testEndpoint';
    const ENDPOINT_ENV_VAR = 'MOMENTO_ENDPOINT';
    const TEST_V2_API_KEY = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJ0IjoiZyIsImp0aSI6InNvbWUtaWQifQ.GMr9nA6HE0ttB6llXct_2Sg5-fOKGFbJCdACZFgNbN1fhT6OPg_hVc8ThGzBrWC_RlsBpLA1nzqK3SOJDXYxAw';
    const TEST_V1_API_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJPbmxpbmUgSldUIEJ1aWxkZXIiLCJpYXQiOjE2NzgzMDU4MTIsImV4cCI6NDg2NTUxNTQxMiwiYXVkIjoiIiwic3ViIjoianJvY2tldEBleGFtcGxlLmNvbSJ9.8Iy8q84Lsr-D3YCo_HP4d-xjHdT8UCIuvAYcxhFMyz8';
    const TEST_PRE_V1_API_KEY = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ1c2VyQHRlc3QuY29tIiwiY3AiOiJjb250cm9sLnRlc3QuY29tIiwiYyI6ImNhY2hlLnRlc3QuY29tIn0.c0Z8Ipetl6raCNHSHs7Mpq3qtWkFy4aLvGhIFR4CoR0OnBdGbdjN-4E58bAabrSGhRA8-B2PHzgDd4JF4clAzg';

    public function setUp(): void
    {
        if (!isset($_SERVER[self::AUTH_TOKEN_NAME])) {
            throw new RuntimeException(
                sprintf("Integration tests require %s environment variable", self::AUTH_TOKEN_NAME)
            );
        }

        putenv(self::V2_API_KEY_ENV_VAR . '=' . self::TEST_V2_API_KEY);
        putenv(self::ENDPOINT_ENV_VAR . '=' . self::TEST_ENDPOINT);
        putenv('ALTERNATE_MOMENTO_ENDPOINT' . '=' . self::TEST_ENDPOINT);

        $this->v1AuthToken = $_SERVER[self::AUTH_TOKEN_NAME];
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
        $authProvider = new StringMomentoTokenProvider($this->v1AuthToken);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testStringToken_V2ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new StringMomentoTokenProvider(self::TEST_V2_API_KEY);
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
        $authProvider = StringMomentoTokenProvider::fromString($this->v1AuthToken);
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

    public function testEnvVarToken_V2ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoTokenProvider(self::V2_API_KEY_ENV_VAR);
    }

    public function testEnvMomentoV2TokenProvider_HappyPath()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoV2TokenProvider(self::V2_API_KEY_ENV_VAR, self::ENDPOINT_ENV_VAR);
    }

    public function testFromEnvVarV2_fromEnvVar_HappyPath()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = EnvMomentoV2TokenProvider::fromEnvironmentVariablesV2(self::V2_API_KEY_ENV_VAR, self::ENDPOINT_ENV_VAR);
    }

    public function testFromEnvVarV2_fromEnvVar_HappyPath_DefaultEndpoint()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = EnvMomentoV2TokenProvider::fromEnvironmentVariablesV2(self::V2_API_KEY_ENV_VAR);
    }

    public function testFromEnvVarV2_fromEnvVar_HappyPath_AlternateEndpoint()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = EnvMomentoV2TokenProvider::fromEnvironmentVariablesV2(self::V2_API_KEY_ENV_VAR, 'ALTERNATE_MOMENTO_ENDPOINT');
    }

    public function testEnvMomentoV2TokenProvider_ApiKeyEnvVarNameEmpty()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoV2TokenProvider('', self::ENDPOINT_ENV_VAR);
    }

    public function testEnvMomentoV2TokenProvider_EndpointEnvVarEmpty()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoV2TokenProvider(self::V2_API_KEY_ENV_VAR, '');
    }

    public function testEnvMomentoV2TokenProvider_NonexistentApiKeyEnvVarName()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoV2TokenProvider('DOES_NOT_EXIST', self::ENDPOINT_ENV_VAR);
    }

    public function testEnvMomentoV2TokenProvider_NonexistentEndpointEnvVar()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new EnvMomentoV2TokenProvider(self::V2_API_KEY_ENV_VAR, 'DOES_NOT_EXIST');
    }

    public function testApiKeyV2TokenProvider_HappyPath()
    {
        $authProvider = new ApiKeyV2TokenProvider(self::TEST_V2_API_KEY, self::TEST_ENDPOINT);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
    }

    public function testFromApiKeyV2_HappyPath()
    {
        $authProvider = ApiKeyV2TokenProvider::fromApiKeyV2(self::TEST_V2_API_KEY, self::TEST_ENDPOINT);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
    }

    public function testApiKeyV2TokenProvider_KeyEmpty()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new ApiKeyV2TokenProvider('', self::TEST_ENDPOINT);
    }

    public function testApiKeyV2TokenProvider_EndpointEmpty()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new ApiKeyV2TokenProvider(self::TEST_V2_API_KEY, '');
    }

    public function testApiKeyV2TokenProvider_V1ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new ApiKeyV2TokenProvider($this->v1AuthToken, self::TEST_ENDPOINT);
    }

    public function testApiKeyV2TokenProvider_PreV1ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new ApiKeyV2TokenProvider(self::TEST_PRE_V1_API_KEY, self::TEST_ENDPOINT);
    }

    public function testDisposableTokenProvider_V2ApiKey()
    {
        $this->expectException(InvalidArgumentError::class);
        $authProvider = new DisposableTokenProvider(self::TEST_V2_API_KEY);
    }

    public function testFromDisposableToken_V1ApiKey()
    {
        $authProvider = DisposableTokenProvider::fromDisposableToken($this->v1AuthToken);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testDisposableTokenProvider_V1ApiKey()
    {
        $authProvider = new DisposableTokenProvider($this->v1AuthToken);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }

    public function testDisposableTokenProvider_PreV1ApiKey()
    {
        $authProvider = new DisposableTokenProvider(self::TEST_PRE_V1_API_KEY);
        $this->assertNotNull($authProvider->getAuthToken());
        $this->assertNotNull($authProvider->getControlEndpoint());
        $this->assertNotNull($authProvider->getCacheEndpoint());
        $this->assertNull($authProvider->getTrustedControlEndpointCertificateName());
        $this->assertNull($authProvider->getTrustedCacheEndpointCertificateName());
    }
}
