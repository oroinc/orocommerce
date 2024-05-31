<?php

namespace Oro\Bundle\UPSBundle\Client\Factory\Basic;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

/**
 * Base implementation of UPS Client factory
 */
class BasicUpsClientFactory implements UpsClientFactoryInterface
{
    private bool $isOAuthConfigured = false;

    public function __construct(
        private RestClientFactoryInterface $restClientFactory,
        private UpsClientUrlProviderInterface $upsClientUrlProvider,
        private UpsClientUrlProviderInterface $upsClientUrlOAuthProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createUpsClient($isTestMode): RestClientInterface
    {
        if ($this->isOAuthConfigured) {
            $url = $this->upsClientUrlOAuthProvider->getUpsUrl($isTestMode);
        } else {
            $url = $this->upsClientUrlProvider->getUpsUrl($isTestMode);
        }

        return $this->restClientFactory->createRestClient($url, []);
    }

    public function setIsOAuthConfigured(bool $isConfigured): BasicUpsClientFactory
    {
        $this->isOAuthConfigured = $isConfigured;

        return $this;
    }
}
