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

    #[\Override]
    public function getRequest(): FedexRequestInterface
    {
        return $this->request;
    }

    #[\Override]
    public function getSettings(): FedexIntegrationSettings
    {
        return $this->settings;
    }

    #[\Override]
    public function getCacheKey(): string
    {
        return (string) crc32(serialize($this->request->getRequestData()));
    }
}
