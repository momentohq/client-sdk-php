<?php
declare(strict_types=1);

namespace Momento\Auth;

interface ICredentialProvider
{
    public function getAuthToken(): string;

    public function getControlEndpoint(): string;

    public function getCacheEndpoint(): string;

    public function getTrustedControlEndpointCertificateName(): string|null;

    public function getTrustedCacheEndpointCertificateName(): string|null;
}
