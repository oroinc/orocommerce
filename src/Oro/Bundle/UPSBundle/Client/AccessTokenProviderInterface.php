<?php

namespace Oro\Bundle\UPSBundle\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * OAuth access token provider interface
 */
interface AccessTokenProviderInterface
{
    public function getAccessToken(
        UPSTransport $transport,
        RestClientInterface $client,
        bool $isCheckMode = false
    ): ?string;
}
