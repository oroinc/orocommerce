<?php

namespace Oro\Bundle\UPSBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

class ShippingPriceCache
{
    /**
     * 24 hours, 60 * 60 * 24
     */
    const LIFETIME = 86400;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ShippingPriceCacheKey $key
     * @return bool
     */
    public function containsPrice(ShippingPriceCacheKey $key)
    {
        return $this->containsPriceByStringKey($this->generateStringKey($key));
    }

    /**
     * @param string $stringKey
     * @return bool
     */
    protected function containsPriceByStringKey($stringKey)
    {
        return $this->cache->contains($stringKey);
    }

    /**
     * @param ShippingPriceCacheKey $key
     * @return bool|Price
     */
    public function fetchPrice(ShippingPriceCacheKey $key)
    {
        $stringKey = $this->generateStringKey($key);
        if (!$this->containsPriceByStringKey($stringKey)) {
            return false;
        }
        return $this->cache->fetch($stringKey);
    }

    /**
     * @param ShippingPriceCacheKey $key
     * @param Price $price
     * @return $this
     */
    public function savePrice(ShippingPriceCacheKey $key, Price $price)
    {
        $interval = 0;
        $invalidateCacheAt = $key->getTransport()->getInvalidateCacheAt();
        if ($invalidateCacheAt) {
            $interval = $invalidateCacheAt->getTimestamp() - time();
        }
        if ($interval <= 0 || $interval > static::LIFETIME) {
            $interval = static::LIFETIME;
        }
        $this->cache->save($this->generateStringKey($key), $price, $interval);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->cache->deleteAll();
    }

    /**
     * @param UPSTransport $transport
     * @param PriceRequest $priceRequest
     * @param string $methodId
     * @param string $typeId
     * @return ShippingPriceCacheKey
     */
    public function createKey(
        UPSTransport $transport,
        PriceRequest $priceRequest,
        $methodId,
        $typeId
    ) {
        return (new ShippingPriceCacheKey())->setTransport($transport)->setPriceRequest($priceRequest)
            ->setMethodId($methodId)->setTypeId($typeId);
    }

    /**
     * @param ShippingPriceCacheKey $key
     * @return string
     */
    protected function generateStringKey(ShippingPriceCacheKey $key)
    {
        $invalidateAt = '';
        if ($key->getTransport() && $key->getTransport()->getInvalidateCacheAt()) {
            $invalidateAt = $key->getTransport()->getInvalidateCacheAt()->getTimestamp();
        }
        return implode('_', [
            $key->generateKey(),
            $invalidateAt
        ]);
    }
}
