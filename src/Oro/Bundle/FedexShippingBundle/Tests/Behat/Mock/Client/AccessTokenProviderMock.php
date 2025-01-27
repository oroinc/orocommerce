<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Behat\Mock\Client;

use Oro\Bundle\FedexShippingBundle\Client\RateService\AccessTokenProviderInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class AccessTokenProviderMock implements AccessTokenProviderInterface
{
    #[\Override]
    public function getAccessToken(
        FedexIntegrationSettings $settings,
        string $baseUrl,
        bool $isCheckMode = false
    ): ?string {
        return 'behat_mock_access_token';
    }
}
