<?php

namespace Oro\Bundle\UPSBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

class ShippingPriceCache
{
    /**
     * 24 hours, 60 * 60 * 24
     */
    const LIFETIME = 86400;

    const NAME_SPACE = 'oro_ups_shipping_price';

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var LifetimeProviderInterface
     */
    protected $lifetimeProvider;

    public function __construct(CacheProvider $cache, LifetimeProviderInterface $lifetimeProvider)
    {
        $this->cache = $cache;
        $this->lifetimeProvider = $lifetimeProvider;
    }

    /**
     * @param ShippingPriceCacheKey $key
     *
     * @return bool
     */
    public function containsPrice(ShippingPriceCacheKey $key)
    {
        $this->setNamespace($key->getTransport()->getId());

        return $this->containsPriceByStringKey($this->generateStringKey($key));
    }

    /**
     * @param string $stringKey
     *
     * @return bool
     */
    protected function containsPriceByStringKey($stringKey)
    {
        return $this->cache->contains($stringKey);
    }

    /**
     * @param ShippingPriceCacheKey $key
     *
     * @return bool|Price
     */
    public function fetchPrice(ShippingPriceCacheKey $key)
    {
        $this->setNamespace($key->getTransport()->getId());

        $stringKey = $this->generateStringKey($key);
        if (!$this->containsPriceByStringKey($stringKey)) {
            return false;
        }

        return $this->cache->fetch($stringKey);
    }

    /**
     * @param ShippingPriceCacheKey $key
     * @param Price                 $price
     *
     * @return $this
     */
    public function savePrice(ShippingPriceCacheKey $key, Price $price)
    {
        $this->setNamespace($key->getTransport()->getId());

        $lifetime = $this->lifetimeProvider->getLifetime($key->getTransport(), static::LIFETIME);

        $this->cache->save($this->generateStringKey($key), $price, $lifetime);

        return $this;
    }

    /**
     * @param integer $transportId
     */
    public function deleteAll($transportId)
    {
        $this->setNamespace($transportId);
        $this->cache->deleteAll();
    }

    /**
     * @param UPSTransport $transport
     * @param PriceRequest $priceRequest
     * @param string       $methodId
     * @param string       $typeId
     *
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
     *
     * @return string
     */
    protected function generateStringKey(ShippingPriceCacheKey $key)
    {
        return $this->lifetimeProvider->generateLifetimeAwareKey($key->getTransport(), $key->generateKey());
    }

    /**
     * @param integer $id
     */
    protected function setNamespace($id)
    {
        $this->cache->setNamespace(self::NAME_SPACE.'_'.$id);
    }
}
