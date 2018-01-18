<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexResponseCacheInterface
{
    /**
     * @param FedexResponseCacheKeyInterface $key
     *
     * @return bool
     */
    public function has(FedexResponseCacheKeyInterface $key): bool;

    /**
     * @param FedexResponseCacheKeyInterface $key
     *
     * @return FedexRateServiceResponseInterface|null
     */
    public function get(FedexResponseCacheKeyInterface $key);

    /**
     * @param FedexResponseCacheKeyInterface             $key
     * @param FedexRateServiceResponseInterface $response
     *
     * @return bool
     */
    public function set(FedexResponseCacheKeyInterface $key, FedexRateServiceResponseInterface $response): bool;

    /**
     * @param FedexResponseCacheKeyInterface $key
     *
     * @return bool
     */
    public function delete(FedexResponseCacheKeyInterface $key): bool;
    
    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return bool
     */
    public function deleteAll(FedexIntegrationSettings $settings): bool;
}
