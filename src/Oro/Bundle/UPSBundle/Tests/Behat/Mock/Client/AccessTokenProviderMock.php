<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat\Mock\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\UPSBundle\Client\AccessTokenProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

class AccessTokenProviderMock implements AccessTokenProviderInterface
{
    public function getAccessToken(
        UPSTransport $transport,
        RestClientInterface $client,
        bool $isCheckMode = false
    ): ?string {
        return 'behat_mock_access_token';
    }
}
