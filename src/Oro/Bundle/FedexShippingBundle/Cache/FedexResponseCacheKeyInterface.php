<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexResponseCacheKeyInterface
{
    /**
     * @return FedexRequestInterface
     */
    public function getRequest(): FedexRequestInterface;

    /**
     * @return FedexIntegrationSettings
     */
    public function getSettings(): FedexIntegrationSettings;

    /**
     * @return string
     */
    public function getCacheKey(): string;
}
