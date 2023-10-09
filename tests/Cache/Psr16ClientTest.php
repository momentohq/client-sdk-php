<?php
declare(strict_types=1);

namespace Momento\Tests\Cache;

use DateTime;
use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\InvalidArgumentException;
use Momento\Cache\Errors\NotImplementedException;
use Momento\Cache\Psr16CacheClient;
use Momento\Cache\CacheClient;
use Momento\Config\Configuration;
use Momento\Config\Configurations\Laptop;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\NullLoggerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers Psr16CacheClient
 */
class Psr16ClientTest extends TestCase
{
    private int $DEFAULT_TTL_SECONDS = 10;
    private Psr16CacheClient $client;

    public function setUp(): void
    {
        $loggerFactory = new NullLoggerFactory();
        $grpcConfiguration = new StaticGrpcConfiguration(5000);
        $transportStrategy = new StaticTransportStrategy($grpcConfiguration);
        $configuration = new Configuration($loggerFactory, $transportStrategy);
        $authProvider = new EnvMomentoTokenProvider("TEST_AUTH_TOKEN");
        $this->client = new Psr16CacheClient($configuration, $authProvider, $this->DEFAULT_TTL_SECONDS);
    }

    public function testDateIntervalToSeconds()
    {
        $dateInterval = new \DateInterval("PT1H");
        $this->assertEquals(3600, Psr16CacheClient::dateIntervalToSeconds($dateInterval));

        $d1 = new DateTime("now");
        $d2 = clone $d1;
        $d2->modify("-1 hour");
        $dateInterval = $d2->diff($d1);
        $this->assertEquals(3600, Psr16CacheClient::dateIntervalToSeconds($dateInterval));
    }

    public function dataTypeProvider(): array
    {
        return [
            ["hello i am a string!"],
            [1],
            [1.07],
            [["color" => "red", "age" => 98]],
            [[1, 2, 3, 4, 5]],
            [[0 => 0, 1 => 2, 2 => 4, "3" => 6.00000001, "blueberries", "black" => "white", "grey"]],
            [[pack("nvc*", 0x1234, 0x5678, 65, 66)]],
            [[false]],
            [[null]],
            [[-12]],
            [[-12.0576]],
            [
                "let me tell you a story",
                ["about" => ["nested", "data"]],
                [
                    "so" => [
                        "very" => [
                            "nested" => [
                                "!!!!!!!!!!"
                            ]
                        ]
                    ],
                    [
                        ["hello i am a string!"],
                        [1],
                        [1.07],
                        [["color" => "red", "age" => 98]],
                        [[1, 2, 3, 4, 5]],
                        [[0 => 0, 1 => 2, 2 => 4, "3" => 6.00000001, "blueberries", "black" => "white", "grey"]],
                        [[pack("nvc*", 0x1234, 0x5678, 65, 66)]],
                        [[false]],
                        [[null]],
                        [[-12]],
                        [[-12.0576]]
                    ],
                    ["false" => true, true => "false"],
                ]
            ]
        ];
    }

    public function testOverrideDefaultCacheName()
    {
        $testCacheName = "PSR16-test-cache";
        $configuration = Laptop::latest();
        $authProvider = new EnvMomentoTokenProvider("TEST_AUTH_TOKEN");
        $client = new CacheClient(
            $configuration, $authProvider, $this->DEFAULT_TTL_SECONDS
        );
        $psrClient = new Psr16CacheClient(
            $configuration, $authProvider, $this->DEFAULT_TTL_SECONDS, null, $testCacheName
        );
        $listResponse = $client->listCaches();
        $this->assertNull($listResponse->asError());
        $gotMatch = false;
        foreach ($listResponse->asSuccess()->caches() as $cache) {
            $cacheName = $cache->name();
            if ($cacheName === $testCacheName) {
                $gotMatch = true;
                break;
            }
        }
        $this->assertTrue($gotMatch);
        $deleteResponse = $client->deleteCache($testCacheName);
        $this->assertNull($deleteResponse->asError());
    }

    /**
     * @dataProvider dataTypeProvider
     */
    public function testGetSetDelete_HappyPath_MultipleTypes($value)
    {
        $key = "myKey";

        $this->assertTrue($this->client->set($key, $value));

        $cache_value = $this->client->get($key);
        $this->assertSame($value, $cache_value);

        $this->assertTrue($this->client->delete($key));
        $this->assertNull($this->client->get($key));
    }

    public function testGetSetDelete_HappyPath_Object()
    {
        $this->markTestSkipped("Exception payload exceeds item limit.");
        $key = "myKey";
        $exc = new \Exception("imaserializedexceptionobject!");

        $this->assertTrue($this->client->set($key, $exc));

        $cache_value = $this->client->get($key);
        $this->assertEquals($exc, $cache_value);

        $this->assertTrue($this->client->delete($key));
        $this->assertNull($this->client->get($key));
    }

    public function testHas()
    {
        $keys = ["abc", "def", "ghi", "jkl"];
        foreach ($keys as $key) {
            $this->assertTrue($this->client->set($key, "myValue"));
        }
        foreach ($keys as $key) {
            $this->assertTrue($this->client->has($key));
            $this->assertTrue($this->client->delete($key));
        }
        foreach ($keys as $key) {
            $this->assertFalse($this->client->has($key));
        }
    }

    public function testClear()
    {
        $this->expectException(NotImplementedException::class);
        $this->assertTrue($this->client->clear());
    }

    public function testGetMultiple_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        foreach ($items as $k => $v) {
            $this->assertTrue($this->client->set($k, $v));
        }
        $multiValues = $this->client->getMultiple(array_keys($items));
        $this->assertSame($items, $multiValues);
    }

    public function testSetMultiple_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        $this->assertTrue($this->client->setMultiple($items));
        $values = [];
        foreach ($items as $k => $v) {
            $this->assertTrue((bool)$values[$k] = $this->client->get($k, $v));
        }
        $this->assertSame($items, $values);
    }

    public function testDeleteMultiple_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        $this->assertTrue($this->client->setMultiple($items));
        $values = [];
        foreach ($items as $k => $v) {
            $this->assertTrue((bool)$values[$k] = $this->client->get($k, $v));
        }
        $this->assertSame($items, $values);
        $this->assertTrue($this->client->deleteMultiple(array_keys($items)));
        foreach ($items as $k => $v) {
            $this->assertFalse($this->client->has($k, $v));
        }
    }

    public function testDeleteMany_Subset_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        $this->assertTrue($this->client->setMultiple($items));
        $values = [];
        foreach ($items as $k => $v) {
            $this->assertTrue((bool)$values[$k] = $this->client->get($k, $v));
        }
        $this->assertSame($items, $values);
        $this->assertTrue($this->client->deleteMultiple(["key1", "key2"]));
        $this->assertFalse($this->client->has("key1"));
        $this->assertFalse($this->client->has("key2"));
        $this->assertTrue($this->client->has("key3"));
    }

    public function testAllRequiredCharsAreSupported()
    {
        $requiredChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0987654321._";
        $this->assertTrue($this->client->set($requiredChars, "woohoo"));
    }

    public function testBadKeyThrowsInvalidArgumentException()
    {
        $required_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0987654321._";
        $badChars = ["{", "}", "(", ")", "/", "\\", "@", ":"];
        foreach ($badChars as $char) {
            $this->expectException(InvalidArgumentException::class);
            $key = $required_chars . $char;
            $this->client->set($key, "oops");
        }
    }

    public function testBadKeyThrowsInvalidArgumentException_GetMany()
    {
        $items = ["yourecool" => 1, "youreok" => 2, "arg/h" => 3];
        $this->expectException(InvalidArgumentException::class);
        $this->client->getMultiple(array_keys($items));
    }

    public function testBadKeyThrowsInvalidArgumentException_SetMany()
    {
        $items = ["yourecool" => 1, "youreok" => 2, "arg/h" => 3];
        $this->expectException(InvalidArgumentException::class);
        $this->client->setMultiple($items);
    }

    public function testGetMultiple_BadKeys()
    {
        $keys = ["notthere", "nopestillnothing", "verywellhidden"];
        $results = $this->client->getMultiple($keys);
        foreach ($results as $key => $value) {
            $this->assertNull($value);
        }
    }

    public function testNegativeIntegerTtl()
    {
        $key = "myKey";
        $this->assertTrue($this->client->set($key, "value", 600));
        $this->assertTrue($this->client->has($key));
        $this->assertTrue($this->client->set($key, "value", 0));
        $this->assertFalse($this->client->has($key));
    }

    public function testNegativeDateInterval()
    {
        $key = "myKey";
        $di = new \DateInterval("PT1H");
        $di->invert = 1;
        $this->assertTrue($this->client->set($key, "value", 600));
        $this->assertTrue($this->client->has($key));
        $this->assertTrue($this->client->set($key, "value", $di));
        $this->assertFalse($this->client->has($key));
    }

    public function testNullTll()
    {
        $key = "myKey";
        $this->assertTrue($this->client->set($key, "value", null));
        $this->assertTrue($this->client->has($key));
    }

    public function testMissingAuthToken()
    {
        $this->expectException(InvalidArgumentError::class);
        $badProvider = new EnvMomentoTokenProvider("FAKE_AUTH_TOKEN");
    }


    public function testExpiresAfterTtl()
    {
        $key = uniqid();
        $value = uniqid();
        $this->assertTrue($this->client->set($key, $value, 2));
        $this->assertNotNull($this->client->get($key));
        sleep(4);
        $this->assertNull($this->client->get($key));
    }

    public function testSetWithDifferentTtls()
    {
        $key1 = uniqid();
        $key2 = uniqid();
        $this->assertTrue($this->client->set($key1, "1", 2));
        $this->assertTrue($this->client->set($key2, "2", 2));

        $this->assertNotNull($this->client->get($key1));
        $this->assertNotNull($this->client->get($key2));

        sleep(4);

        $this->assertNull($this->client->get($key1));
        $this->assertNull($this->client->get($key2));
    }

}
