<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

class TimeInTransitCacheProvider implements TimeInTransitCacheProviderInterface
{
    const CACHE_LIFETIME = 86400;
    const PICKUP_DATE_CACHE_KEY_FORMAT = 'YmdHi';

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function contains(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate)
    {
        return $this->cacheProvider->contains($this->composeCacheKey($shipToAddress, $shipToAddress, $pickupDate));
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate)
    {
        return $this->cacheProvider->fetch($this->composeCacheKey($shipToAddress, $shipToAddress, $pickupDate));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate)
    {
        return $this->cacheProvider->delete($this->composeCacheKey($shipToAddress, $shipToAddress, $pickupDate));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll()
    {
        return $this->cacheProvider->deleteAll();
    }

    /**
     * {@inheritDoc}
     */
    public function save(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        TimeInTransitResultInterface $timeInTransitResult,
        $lifeTime = self::CACHE_LIFETIME
    ) {
        $cacheKey = $this->composeCacheKey($shipToAddress, $shipToAddress, $pickupDate);

        return $this->cacheProvider->save($cacheKey, $timeInTransitResult, $lifeTime);
    }

    /**
     * @param AddressInterface $shipFromAddress
     * @param AddressInterface $shipToAddress
     * @param \DateTime        $pickupDate
     *
     * @return string
     */
    protected function composeCacheKey(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) {
        return sprintf(
            '%s:%s:%s:%s:%s',
            $shipFromAddress->getCountryIso2(),
            $shipFromAddress->getPostalCode(),
            $shipToAddress->getCountryIso2(),
            $shipToAddress->getPostalCode(),
            $pickupDate->format(self::PICKUP_DATE_CACHE_KEY_FORMAT)
        );
    }
}
