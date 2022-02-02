<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;

/**
 * Provides an interface for FedEx cache adapters.
 */
interface FedexResponseCacheInterface
{
    public function has(FedexResponseCacheKeyInterface $key): bool;

    public function get(FedexResponseCacheKeyInterface $key): FedexRateServiceResponseInterface|null;

    public function set(FedexResponseCacheKeyInterface $key, FedexRateServiceResponseInterface $response): bool;

    public function delete(FedexResponseCacheKeyInterface $key): bool;

    public function deleteAll(): bool;
}
