<?php

namespace Oro\Bundle\FedexShippingBundle\Cache\Factory;

use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKeyInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexResponseCacheKeyFactory implements FedexResponseCacheKeyFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexResponseCacheKeyInterface {
        return new FedexResponseCacheKey($request, $settings);
    }
}
