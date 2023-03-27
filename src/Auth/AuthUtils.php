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
        $exploded = explode(".", $authToken);
        if (count($exploded) != 3) {
            self::throwBadAuthToken();
        }

        try {
            $payload = $exploded[1];
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        } catch (\Exception) {
            self::throwBadAuthToken();
        }

        if ($payload === null) {
            self::throwBadAuthToken();
        }
        return $payload;
    }

}
