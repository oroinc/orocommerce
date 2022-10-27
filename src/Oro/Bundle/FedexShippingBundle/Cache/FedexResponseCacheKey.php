<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexResponseCacheKey implements FedexResponseCacheKeyInterface
{
    /**
     * @var FedexRequestInterface
     */
    private $request;

    /**
     * @var FedexIntegrationSettings
     */
    private $settings;

    public function __construct(FedexRequestInterface $request, FedexIntegrationSettings $settings)
    {
        $this->request = $request;
        $this->settings = $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequest(): FedexRequestInterface
    {
        return $this->request;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings(): FedexIntegrationSettings
    {
        return $this->settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheKey(): string
    {
        return (string) crc32(serialize($this->request->getRequestData()));
    }
}
