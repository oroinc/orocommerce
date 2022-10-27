<?php

namespace Oro\Bundle\UPSBundle\Handler;

use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;

/**
 * Action handler for invalidate UPS shipping prices cache
 */
class InvalidateCacheActionHandler implements InvalidateCacheActionHandlerInterface
{
    const PARAM_TRANSPORT_ID = 'transportId';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

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

    public function __construct(
        DoctrineHelper $doctrineHelper,
        UPSShippingPriceCache $upsPriceCache,
        ShippingPriceCache $shippingPriceCache,
        TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->upsPriceCache = $upsPriceCache;
        $this->shippingPriceCache = $shippingPriceCache;
        $this->timeInTransitCacheProviderFactory = $timeInTransitCacheProviderFactory;
    }

    public function handle(DataStorageInterface $dataStorage)
    {
        $transportId = $dataStorage->get(self::PARAM_TRANSPORT_ID);

        $this->upsPriceCache->deleteAll();
        $this->shippingPriceCache->deleteAllPrices();

        $settings = $this->findSettings($transportId);

        if ($settings !== null) {
            $this->timeInTransitCacheProviderFactory
                ->createCacheProviderForTransport($settings)
                ->deleteAll();
        }
    }

    /**
     * @param int $settingsId
     *
     * @return UPSSettings|null|object
     */
    private function findSettings($settingsId)
    {
        return $this->doctrineHelper->getEntityRepository(UPSSettings::class)->find($settingsId);
    }
}
