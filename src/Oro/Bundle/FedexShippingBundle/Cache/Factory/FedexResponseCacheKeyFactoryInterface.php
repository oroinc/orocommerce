<?php

namespace Oro\Bundle\FedexShippingBundle\Cache\Factory;

use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKeyInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * Defines the contract for creating FedEx response cache key objects.
 */
interface FedexResponseCacheKeyFactoryInterface
{
    public function create(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexResponseCacheKeyInterface;
}
