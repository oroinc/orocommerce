<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListIdentifierProviderInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Implements combining price strategy based on PriceList priority and minimal prices
 */
class MinimalPricesCombiningStrategy extends AbstractPriceCombiningStrategy implements
    CombinedPriceListIdentifierProviderInterface
{
    const NAME = 'minimal_prices';

    /**
     * @var ShardManager
     */
    protected $shardManager;

    public function __construct(
        Registry $registry,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceListTriggerHandler $triggerHandler,
        ShardManager $shardManager
    ) {
        $this->shardManager = $shardManager;
        parent::__construct($registry, $insertFromSelectQueryExecutor, $triggerHandler);
    }

    protected function getFallbackCombinedPriceList(CombinedPriceList $combinedPriceList): ?CombinedPriceList
    {
        return $this->getCombinedPriceListRelationsRepository()->findFallbackCpl($combinedPriceList);
    }

    protected function getPriceListRelationsNotIncludedInFallback(
        array $combinedPriceListRelation,
        array $fallbackCplRelations
    ): array {
        $fallbackPlIds = array_map(static function (CombinedPriceListToPriceList $relation) {
            return $relation->getPriceList()->getId();
        }, $fallbackCplRelations);

        // Return only relations that contain price lists not included in the fallback CPL
        return array_filter(
            $combinedPriceListRelation,
            static function (CombinedPriceListToPriceList $relation) use ($fallbackPlIds) {
                return !\in_array($relation->getPriceList()->getId(), $fallbackPlIds, true);
            }
        );
    }

    protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceListRelations,
        array $products = [],
        ProgressBar $progressBar = null
    ) {
        if ($this->shardManager->isShardingEnabled()) {
            $progress = 0;
            foreach ($priceListRelations as $priceListRelation) {
                $this->moveProgress($progressBar, $progress, $priceListRelation);
                $this->processRelation($combinedPriceList, $priceListRelation, $products);
            }
        } else {
            $this->massProcessPriceLists($combinedPriceList, $priceListRelations, $products, $progressBar);
        }
    }

    protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products = []
    ): void {
        $this->getCombinedProductPriceRepository()->insertMinimalPricesByPriceList(
            $this->shardManager,
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $products
        );
    }

    private function massProcessPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        array $products = [],
        ProgressBar $progressBar = null
    ): void {
        $progress = 0;
        foreach ($priceLists as $priceListRelation) {
            $this->moveProgress($progressBar, $progress, $priceListRelation);
        }

        $this->getCombinedProductPriceRepository()->insertMinimalPricesByPriceLists(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $this->getUniqueSortedPriceListIds($priceLists),
            $products
        );
    }

    /**
     * @deprecated Will be removed in 5.1
     */
    public function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $relatedCombinedPriceList
    ) {
        $this->processCombinedPriceListRelationWithProducts(
            $combinedPriceList,
            $relatedCombinedPriceList
        );
    }

    protected function processCombinedPriceListRelationWithProducts(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $fallbackCpl,
        array $products = []
    ): void {
        $this->getCombinedProductPriceRepository()->insertMinimalPricesByCombinedPriceListIncludingProducts(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $fallbackCpl,
            $products
        );
    }

    public function getCombinedPriceListIdentifier(array $priceListsRelations): string
    {
        $key = $this->getUniqueSortedPriceListIds($priceListsRelations);

        return md5(implode(self::GLUE, $key));
    }

    private function getUniqueSortedPriceListIds(array $priceListsRelations): array
    {
        $key = [];
        // Minimal strategy does not use merge flag, skip it and collect only IDs to create identifier
        foreach ($priceListsRelations as $priceListSequenceMember) {
            $key[] = $priceListSequenceMember->getPriceList()->getId();
        }
        // Minimal prices will be added once from each PL, duplicates should be removed.
        $key = array_unique($key);
        // Minimal prices will be added independently on order, so we can sort IDs to get same CPL
        sort($key);

        return $key;
    }
}
