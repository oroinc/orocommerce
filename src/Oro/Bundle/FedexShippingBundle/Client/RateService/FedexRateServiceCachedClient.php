<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Cache\Factory\FedexResponseCacheKeyFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexRateServiceCachedClient implements FedexRateServiceBySettingsClientInterface
{
    /**
     * @var FedexRateServiceClientInterface
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

    /**
     * @param FedexRateServiceClientInterface       $rateServiceClient
     * @param FedexResponseCacheInterface           $cache
     * @param FedexResponseCacheKeyFactoryInterface $cacheKeyFactory
     */
    public function __construct(
        FedexRateServiceClientInterface $rateServiceClient,
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

        $response = $this->rateServiceClient->send($request);
        if ($response->getSeverityType() === FedexRateServiceResponse::SEVERITY_SUCCESS) {
            $this->cache->set($cacheKey, $response);
        }

        return $response;
    }
}
