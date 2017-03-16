<?php

namespace Oro\Bundle\UPSBundle\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorageInterface;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;

class InvalidateCacheActionHandler implements InvalidateCacheActionHandlerInterface
{
    const PARAM_TRANSPORT_ID = 'transportId';

    /**
     * @var UPSShippingPriceCache
     */
    protected $upsPriceCache;

    /**
     * @var ShippingPriceCache
     */
    protected $shippingPriceCache;

    /**
     * @param UPSShippingPriceCache $upsPriceCache
     * @param ShippingPriceCache    $shippingPriceCache
     */
    public function __construct(
        UPSShippingPriceCache $upsPriceCache,
        ShippingPriceCache $shippingPriceCache
    ) {
        $this->upsPriceCache = $upsPriceCache;
        $this->shippingPriceCache = $shippingPriceCache;
    }

    /**
     * @param InvalidateCacheDataStorageInterface $dataStorage
     */
    public function handle(InvalidateCacheDataStorageInterface $dataStorage)
    {
        $this->upsPriceCache->deleteAll($dataStorage->get(self::PARAM_TRANSPORT_ID));
        $this->shippingPriceCache->deleteAllPrices();
    }
}
