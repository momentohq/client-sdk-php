<?php

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;

class AuthUtils
{

    private static function throwBadAuthToken() {
        throw new InvalidArgumentError('Invalid Momento auth token.');
    }

    public static function parseAuthToken(string $authToken) : array {
        $exploded = explode (".", $authToken);
        if (count($exploded) != 3) {
            self::throwBadAuthToken();
        }
        list($header, $payload, $signature) = $exploded;
        $token = json_decode(base64_decode($payload), true);
        if ($token === null) {
            self::throwBadAuthToken();
        }
        return $token;
    }

}
