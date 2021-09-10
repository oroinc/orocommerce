<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

class TimeInTransitCacheProvider implements TimeInTransitCacheProviderInterface
{
    const CACHE_LIFETIME = 86400;
    const PICKUP_DATE_CACHE_KEY_FORMAT = 'YmdH';

    /**
     * @var UPSSettings
     */
    private $settings;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var LifetimeProviderInterface
     */
    private $lifetimeProvider;

    public function __construct(
        UPSSettings $settings,
        CacheProvider $cacheProvider,
        LifetimeProviderInterface $lifetimeProvider
    ) {
        $this->settings = $settings;
        $this->cacheProvider = $cacheProvider;
        $this->lifetimeProvider = $lifetimeProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function contains(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate)
    {
        return $this->cacheProvider->contains($this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate));
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate)
    {
        return $this->cacheProvider->fetch($this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate)
    {
        return $this->cacheProvider->delete($this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate));
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
        $cacheKey = $this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate);

        $lifeTime = $this->lifetimeProvider->getLifetime($this->settings, $lifeTime);

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
        $key = sprintf(
            '%s:%s:%s:%s:%s',
            $shipFromAddress->getCountryIso2(),
            $shipFromAddress->getPostalCode(),
            $shipToAddress->getCountryIso2(),
            $shipToAddress->getPostalCode(),
            $pickupDate->format(self::PICKUP_DATE_CACHE_KEY_FORMAT)
        );

        return $this->lifetimeProvider->generateLifetimeAwareKey($this->settings, $key);
    }
}
