<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Cache\Factory\FedexResponseCacheKeyFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * FedEx API client that can cahe own result.
 */
class FedexRateServiceCachedClient implements FedexRateServiceBySettingsClientInterface
{
    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $soapRateServiceClient;

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
        FedexRateServiceBySettingsClientInterface $soapRateServiceClient,
        FedexResponseCacheInterface $cache,
        FedexResponseCacheKeyFactoryInterface $cacheKeyFactory
    ) {
        $this->rateServiceClient = $rateServiceClient;
        $this->soapRateServiceClient = $soapRateServiceClient;
        $this->cache = $cache;
        $this->cacheKeyFactory = $cacheKeyFactory;
    }

    #[\Override]
    public function send(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexRateServiceResponseInterface {
        $cacheKey = $this->cacheKeyFactory->create($request, $settings);

        $response = $this->cache->get($cacheKey);
        if ($response) {
            return $response;
        }

        $client = $this->soapRateServiceClient;
        if ($settings->getClientSecret() && $settings->getClientId()) {
            $client = $this->rateServiceClient;
        }

        $response = $client->send($request, $settings);
        if ($response->isSuccessful()) {
            $this->cache->set($cacheKey, $response);
        }

        return $response;
    }
}
