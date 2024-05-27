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
    public const PARAM_TRANSPORT_ID = 'transportId';

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private UPSShippingPriceCache $upsPriceCache,
        private ShippingPriceCache $shippingPriceCache,
        private TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory
    ) {
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
