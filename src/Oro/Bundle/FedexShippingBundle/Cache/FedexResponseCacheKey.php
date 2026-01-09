<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * Encapsulates a FedEx request and integration settings for cache key generation.
 *
 * This class combines a FedEx request with its associated integration settings
 * and provides a method to generate a unique cache key based on the request data
 * using CRC32 hashing of the serialized request.
 */
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
