<?php

namespace Oro\Bundle\UPSBundle\Client\Factory\Basic;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

/**
 * Base implementation of UPS Client factory
 */
class BasicUpsClientFactory implements UpsClientFactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    private $restClientFactory;

    /**
     * @var UpsClientUrlProviderInterface
     */
    private $upsClientUrlProvider;

    /**
     * @var UpsClientUrlProviderInterface
     */
    private $upsClientUrlOAuthProvider;

    /**
     * @var bool
     */
    private $isOAuthConfigured;

    public function __construct(
        RestClientFactoryInterface $restClientFactory,
        UpsClientUrlProviderInterface $upsClientUrlProvider
    ) {
        $this->restClientFactory = $restClientFactory;
        $this->upsClientUrlProvider = $upsClientUrlProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createUpsClient($isTestMode)
    {
        if ($this->isOAuthConfigured) {
            $url = $this->upsClientUrlOAuthProvider->getUpsUrl($isTestMode);
        } else {
            $url = $this->upsClientUrlProvider->getUpsUrl($isTestMode);
        }

        return $this->restClientFactory->createRestClient($url, []);
    }

    public function setUpsClientUrlOAuthProvider(UpsClientUrlProviderInterface $upsClientUrlOAuthProvider): void
    {
        $this->upsClientUrlOAuthProvider = $upsClientUrlOAuthProvider;
    }

    public function setIsOAuthConfigured(bool $isConfigured): BasicUpsClientFactory
    {
        $this->isOAuthConfigured = $isConfigured;

        return $this;
    }
}
