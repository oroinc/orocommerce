<?php

namespace Oro\Bundle\UPSBundle\Handler;

use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;

class InvalidateCacheActionHandler implements InvalidateCacheActionHandlerInterface
{
    const PARAM_TRANSPORT_ID = 'transportId';

    /**
     * @var UPSShippingPriceCache
     */
    private $upsPriceCache;

    /**
     * @var ShippingPriceCache
     */
    private $shippingPriceCache;

    /**
     * @var TimeInTransitCacheProviderFactoryInterface
     */
    private $timeInTransitCacheProviderFactory;

    /**
     * @param UPSShippingPriceCache                      $upsPriceCache
     * @param ShippingPriceCache                         $shippingPriceCache
     * @param TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory
     */
    public function __construct(
        UPSShippingPriceCache $upsPriceCache,
        ShippingPriceCache $shippingPriceCache,
        TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory
    ) {
        $this->upsPriceCache = $upsPriceCache;
        $this->shippingPriceCache = $shippingPriceCache;
        $this->timeInTransitCacheProviderFactory = $timeInTransitCacheProviderFactory;
    }

    /**
     * @param DataStorageInterface $dataStorage
     */
    public function handle(DataStorageInterface $dataStorage)
    {
        $transportId = $dataStorage->get(self::PARAM_TRANSPORT_ID);

        $this->upsPriceCache->deleteAll($transportId);
        $this->shippingPriceCache->deleteAllPrices();

        $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($transportId)
            ->deleteAll();
    }
}
