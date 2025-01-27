<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * OAuth access token provider interface
 */
interface AccessTokenProviderInterface
{
    public function getAccessToken(
        FedexIntegrationSettings $settings,
        string $baseUrl,
        bool $isCheckMode = false
    ): ?string;
}
