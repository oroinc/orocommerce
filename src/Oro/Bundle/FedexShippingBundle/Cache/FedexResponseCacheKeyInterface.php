<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * Defines the contract for FedEx response cache key objects.
 *
 * Cache keys encapsulate the request and integration settings needed to generate
 * a unique identifier for caching FedEx API responses.
 */
interface FedexResponseCacheKeyInterface
{
    public function getRequest(): FedexRequestInterface;

    public function getSettings(): FedexIntegrationSettings;

    public function getCacheKey(): string;
}
