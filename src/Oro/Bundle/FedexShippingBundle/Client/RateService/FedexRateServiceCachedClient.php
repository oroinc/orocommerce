<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Cache\Factory\FedexResponseCacheKeyFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexRateServiceCachedClient implements FedexRateServiceBySettingsClientInterface
{
    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    /**
     * @var FedexResponseCacheInterface
     */
    private $cache;

    /**
     * @var FedexResponseCacheKeyFactoryInterface
     */
    private $cacheKeyFactory;

    public function __construct(
        FedexRateServiceBySettingsClientInterface $rateServiceClient,
        FedexResponseCacheInterface $cache,
        FedexResponseCacheKeyFactoryInterface $cacheKeyFactory
    ) {
        $this->rateServiceClient = $rateServiceClient;
        $this->cache = $cache;
        $this->cacheKeyFactory = $cacheKeyFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function send(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexRateServiceResponseInterface {
        $cacheKey = $this->cacheKeyFactory->create($request, $settings);

        $response = $this->cache->get($cacheKey);
        if ($response) {
            return $response;
        }

        $response = $this->rateServiceClient->send($request, $settings);
        if ($response->isSuccessful()) {
            $this->cache->set($cacheKey, $response);
        }

        return $response;
    }
}
