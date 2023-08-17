<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Checks whether the rebuilding of the combined price list is required.
 */
class CombinedPriceListBuildTriggerHandler
{
    private ManagerRegistry $managerRegistry;
    private PriceListRelationTriggerHandler $priceListRelationTriggerHandler;
    private ShardManager $shardManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        PriceListRelationTriggerHandler $priceListRelationTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->managerRegistry = $managerRegistry;
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

    public function handlePriceCreation(ProductPrice $productPrice): bool
    {
        $priceList = $productPrice->getPriceList();

        /** @var CombinedPriceListToPriceListRepository $combinedPriceToPriceListRepository */
        $combinedPriceToPriceListRepository = $this->managerRegistry
            ->getRepository(CombinedPriceListToPriceList::class);
        /** @var ProductPriceRepository $productPriceRepository */
        $productPriceRepository = $this->managerRegistry->getRepository(ProductPrice::class);

        if ($priceList->isActive()
            && $combinedPriceToPriceListRepository->hasCombinedPriceListWithPriceList($priceList)
            && $productPriceRepository->isFirstPriceAdded($this->shardManager, $productPrice)
        ) {
            $this->rebuildCombinedPrices($priceList);

            return true;
        }

        return false;
    }

    private function rebuildCombinedPrices(PriceList $priceList): void
    {
        $this->priceListRelationTriggerHandler->handlePriceListStatusChange($priceList);
    }

    private function getRepository(string $className): ObjectRepository
    {
        return $this->managerRegistry->getRepository($className);
    }

    public function isSupported(PriceList $priceList): bool
    {
        /** @var CombinedPriceListToPriceListRepository $combinedPriceToPriceListRepository */
        $combinedPriceToPriceListRepository = $this->getRepository(CombinedPriceListToPriceList::class);
        /** @var ProductPriceRepository $productPriceRepository */
        $productPriceRepository = $this->getRepository(ProductPrice::class);

        return
            $combinedPriceToPriceListRepository->hasCombinedPriceListWithPriceList($priceList)
            xor $productPriceRepository->hasPrices($this->shardManager, $priceList);
    }
}
