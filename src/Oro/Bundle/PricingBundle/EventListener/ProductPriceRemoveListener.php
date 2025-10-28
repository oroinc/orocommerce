<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;

/**
 * Marks CombinedPriceList as not calculated when product prices are removed by PriceList.
 */
final class ProductPriceRemoveListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private const int BATCH_SIZE = 100;

    private int $batchSize = self::BATCH_SIZE;

    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize > 0 ? $batchSize : self::BATCH_SIZE;
    }

    public function onRemoveAfter(ProductPricesRemoveAfter $event): void
    {
        $priceList = $event->getArgs()['priceList'] ?? null;
        if (!$priceList) {
            return;
        }

        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $cpl2plRepo = $this->registry->getRepository(CombinedPriceListToPriceList::class);
        $cplRepo = $this->registry->getRepository(CombinedPriceList::class);

        $cplIds = [];
        foreach ($cpl2plRepo->getCombinedPriceListsByActualPriceLists([$priceList]) as $cpl) {
            $cplIds[] = $cpl->getId();
            if (count($cplIds) === $this->batchSize) {
                $cplRepo->setAsNotCalculated($cplIds);
                $cplIds = [];
            }
        }

        if (!empty($cplIds)) {
            $cplRepo->setAsNotCalculated($cplIds);
        }
    }
}
