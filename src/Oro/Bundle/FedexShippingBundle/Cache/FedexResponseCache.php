<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexResponseCache implements FedexResponseCacheInterface
{
    /**
     * @internal
     */
    const NAMESPACE = 'oro_fedex_shipping_price';

    /**
     * @internal 24 hours, 60 * 60 * 24
     */
    const LIFETIME = 86400;

    /**
     * @var CacheProvider
     */
    private $cache;

    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function has(FedexResponseCacheKeyInterface $key): bool
    {
        $this->setNamespace($key->getSettings());

        return $this->cache->contains($key->getCacheKey());
    }

    /**
     * {@inheritDoc}
     */
    public function get(FedexResponseCacheKeyInterface $key)
    {
        $this->setNamespace($key->getSettings());

        $response = $this->cache->fetch($key->getCacheKey());
        if ($response === false) {
            return null;
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function set(FedexResponseCacheKeyInterface $key, FedexRateServiceResponseInterface $response): bool
    {
        $this->setNamespace($key->getSettings());

        $invalidateAt = $this->getInvalidateAt($key->getSettings());

        return $this->cache->save($key->getCacheKey(), $response, $invalidateAt);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(FedexResponseCacheKeyInterface $key): bool
    {
        $this->setNamespace($key->getSettings());

        if (!$this->has($key)) {
            return false;
        }

        return $this->cache->delete($key->getCacheKey());
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll(FedexIntegrationSettings $settings): bool
    {
        $this->setNamespace($settings);

        return $this->cache->deleteAll();
    }

    private function getInvalidateAt(FedexIntegrationSettings $settings): int
    {
        $interval = 0;

        $invalidateAt = $settings->getInvalidateCacheAt();
        if ($invalidateAt) {
            $interval = $invalidateAt->getTimestamp() - time();
        }

        if ($interval <= 0) {
            $interval = static::LIFETIME;
        }

        return $interval;
    }

    private function setNamespace(FedexIntegrationSettings $settings)
    {
        $this->cache->setNamespace(self::NAMESPACE . $settings->getId());
    }
}
