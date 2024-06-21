<?php
declare(strict_types=1);

namespace Momento\Auth;

use Firebase\JWT\JWT;
use Momento\Cache\Errors\InvalidArgumentError;


class AuthUtils
{

    private static function throwBadAuthToken()
    {
        throw new InvalidArgumentError('Invalid Momento auth token.');
    }

    public static function parseAuthToken(string $authToken): object
    {
        if (self::isBase64Encoded($authToken)) {
            return self::parseV1Token($authToken);
        } else {
            return self::parseJwtToken($authToken);
        }
    }

    public static function parseV1Token(string $authToken): object {
        $decoded = base64_decode($authToken);
        $tokenData = json_decode($decoded);
        if (!$tokenData->endpoint || !$tokenData->api_key) {
            self::throwBadAuthToken();
        }
        $payload = new \stdClass();
        $payload->c = "cache.{$tokenData->endpoint}";
        $payload->cp = "control.{$tokenData->endpoint}";
        $payload->storage = "storage.{$tokenData->endpoint}";
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
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
            $payload->storage = null;
            $payload->authToken = $authToken;
        } catch (\Exception $e) {
            self::throwBadAuthToken();
        }

        if ($payload === null) {
            self::throwBadAuthToken();
        }
        return $payload;
    }

    private static function isBase64Encoded(string $s) : bool
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
}
