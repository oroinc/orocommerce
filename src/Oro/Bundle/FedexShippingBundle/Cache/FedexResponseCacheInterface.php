<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexResponseCacheInterface
{
    public function has(FedexResponseCacheKeyInterface $key): bool;

    /**
     * @param FedexResponseCacheKeyInterface $key
     *
     * @return FedexRateServiceResponseInterface|null
     */
    public function get(FedexResponseCacheKeyInterface $key);

    public function set(FedexResponseCacheKeyInterface $key, FedexRateServiceResponseInterface $response): bool;

    public function delete(FedexResponseCacheKeyInterface $key): bool;

    public function deleteAll(FedexIntegrationSettings $settings): bool;
}
