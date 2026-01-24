<?php

namespace Oro\Bundle\FedexShippingBundle\Cache\Factory;

use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKeyInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * Creates cache key objects from FedEx requests and integration settings.
 *
 * This factory encapsulates the creation of {@see FedexResponseCacheKey} instances,
 * which are used to generate unique cache identifiers for FedEx API responses
 * based on request data and integration configuration.
 */
class FedexResponseCacheKeyFactory implements FedexResponseCacheKeyFactoryInterface
{
    #[\Override]
    public function create(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexResponseCacheKeyInterface {
        return new FedexResponseCacheKey($request, $settings);
    }
}
