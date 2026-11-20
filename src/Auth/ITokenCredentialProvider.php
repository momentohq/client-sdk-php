<?php
declare(strict_types=1);

namespace Momento\Auth;

/**
 * Provides information that the AuthClient needs in order to establish a connection to and authenticate with
 * the Momento token service.
 */
interface ITokenCredentialProvider extends ICredentialProvider
{
    /**
     * @return string The host which the Momento client will connect to for Momento token operations.
     */
    public function getTokenEndpoint(): string;
}
