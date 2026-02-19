<?php

declare(strict_types=1);

namespace Momento\Auth;

use JsonException;
use LogicException;
use Momento\Cache\Errors\InvalidArgumentError;


class AuthUtils
{

    private static function throwBadAuthToken()
    {
        throw new InvalidArgumentError('Invalid Momento auth token.');
    }

    private static function urlsafeB64Decode(string $input): string
    {
        $remainder = strlen($input) % 4;

        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    private static function jsonDecode(string $input): object
    {
        $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);

        if (!is_object($obj)) {
            throw new LogicException('Expected JSON object');
        }

        return $obj;
    }

    public static function parseAuthToken(string $authToken): object
    {
        if (self::isBase64Encoded($authToken)) {
            return self::parseV1Token($authToken);
        } else {
            if (self::isV2ApiKey($authToken)) {
                throw new InvalidArgumentError('Received a v2 API key. Are you using the correct key? Or did you mean to use `fromApiKeyV2()` or `fromEnvironmentVariablesV2()` instead?');
            }
            return self::parseJwtToken($authToken);
        }
    }

    public static function parseV1Token(string $authToken): object
    {
        $decoded = base64_decode($authToken);
        $tokenData = json_decode($decoded);
        if (!$tokenData->endpoint || !$tokenData->api_key) {
            self::throwBadAuthToken();
        }
        $payload = new \stdClass();
        $payload->c = "cache.{$tokenData->endpoint}";
        $payload->cp = "control.{$tokenData->endpoint}";
        $payload->authToken = $tokenData->api_key;
        return $payload;
    }

    public static function parseJwtToken(string $authToken): object
    {
        $exploded = explode(".", $authToken);
        if (count($exploded) != 3) {
            self::throwBadAuthToken();
        }

        try {
            $payload = $exploded[1];
            $payload = self::jsonDecode(self::urlsafeB64Decode($payload));
            $payload->authToken = $authToken;
        } catch (\Exception $e) {
            self::throwBadAuthToken();
        }

        if ($payload === null) {
            self::throwBadAuthToken();
        }
        return $payload;
    }

    public static function isBase64Encoded(string $s): bool
    {
        if ((bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s) === false) {
            return false;
        }
        $decoded = base64_decode($s, true);
        if ($decoded === false) {
            return false;
        }
        $encoding = mb_detect_encoding($decoded);
        if (! in_array($encoding, ['UTF-8', 'ASCII'], true)) {
            return false;
        }
        return base64_encode($decoded) === $s;
    }

    public static function isV2ApiKey(string $authToken): bool
    {
        if (self::isBase64Encoded($authToken)) {
            return false;
        }
        $claims = self::parseJwtToken($authToken);
        return isset($claims->t) && $claims->t === 'g';
    }
}
