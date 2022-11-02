<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Checks whether the rebuilding of the combined price list is required.
 */
class CombinedPriceListBuildTriggerHandler
{
    private ManagerRegistry $doctrine;
    private PriceListRelationTriggerHandler $priceListRelationTriggerHandler;
    private ShardManager $shardManager;

    public function __construct(
        ManagerRegistry $doctrine,
        PriceListRelationTriggerHandler $priceListRelationTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->doctrine = $doctrine;
        $this->priceListRelationTriggerHandler = $priceListRelationTriggerHandler;
        $this->shardManager = $shardManager;
    }

    public function handle(PriceList $priceList): bool
    {
        /**
         * If price exists and price list not included to cpl then add price list to cpl and rebuild.
         * If price not exists and price list included to cpl then remove price list from cpl and rebuild.
         */
        if ($this->isSupported($priceList)) {
            $this->rebuildCombinedPrices($priceList);

            return true;
        }

        return false;
    }

    private function rebuildCombinedPrices(PriceList $priceList): void
    {
        $this->priceListRelationTriggerHandler->handlePriceListStatusChange($priceList);
    }

    public function isSupported(PriceList $priceList): bool
    {
        /** @var CombinedPriceListToPriceListRepository $combinedPriceToPriceListRepository */
        $combinedPriceToPriceListRepository = $this->doctrine->getRepository(CombinedPriceListToPriceList::class);
        /** @var ProductPriceRepository $productPriceRepository */
        $productPriceRepository = $this->doctrine->getRepository(ProductPrice::class);

        return
            $combinedPriceToPriceListRepository->hasCombinedPriceListWithPriceList($priceList)
            xor $productPriceRepository->hasPrices($this->shardManager, $priceList);
    }
}
