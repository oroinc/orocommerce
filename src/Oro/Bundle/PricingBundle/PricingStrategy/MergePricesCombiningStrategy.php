<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\DBAL\Driver\Exception;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorNativeSqlInterface;
use Oro\Bundle\PricingBundle\ORM\TempTableManipulatorInterface;

/**
 * Implements combining price strategy base on PriceList priority and additional flag "mergeAllowed"
 */
class MergePricesCombiningStrategy extends AbstractPriceCombiningStrategy
{
    const NAME = 'merge_by_priority';

    private TempTableManipulatorInterface $tempTableManipulator;

    public function setTempTableManipulator(TempTableManipulatorInterface $tempTableManipulator): void
    {
        $this->tempTableManipulator = $tempTableManipulator;
    }

    #[\Override]
    protected function getFallbackCombinedPriceList(CombinedPriceList $combinedPriceList): ?CombinedPriceList
    {
        return $this->getCombinedPriceListRelationsRepository()->findFallbackCplUsingMergeFlag($combinedPriceList);
    }

    #[\Override]
    protected function getPriceListRelationsNotIncludedInFallback(
        array $combinedPriceListRelation,
        array $fallbackCplRelations
    ): array {
        /**
         * Return only head a part of price lists chain that is not included in the fallback (tail)
         * CPL price lists chain.
         * Note! Order is IMPORTANT for Merge by priority strategy, so tail (fallback) must be always in the end
         * and order must be not changed, so head chain must be in the same order with the same merge flags and
         * fallback CPL MUST contain of relations with Merge flag set to TRUE for all.
         *
         * Example CPL: 1t_3f_4t_6f, fallback CPL: 4t_6t. Return chain will consist of 1t_3f
         */
        return array_splice($combinedPriceListRelation, 0, -\count($fallbackCplRelations));
    }

    #[\Override]
    protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceListRelations,
        array $products = []
    ): void {
        if (count($priceListRelations) == 0) {
            return;
        }

        if (count($priceListRelations) > 1 && $this->canUseTempTable($combinedPriceList)) {
            $this->moveFirstPriceListPricesWithTempTable($combinedPriceList, $priceListRelations, $products);

            $this->processPriceListsWithTempTable(
                $combinedPriceList,
                $priceListRelations,
                $products
            );
        } else {
            $this->moveFirstPriceListPrices($combinedPriceList, $priceListRelations, $products);

            foreach ($priceListRelations as $priceListRelation) {
                $this->processRelation($combinedPriceList, $priceListRelation, $products);
            }
        }
    }

    #[\Override]
    protected function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $fallbackCpl,
        array $products = []
    ): void {
        $this->getCombinedProductPriceRepository()->insertPricesByCombinedPriceList(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $fallbackCpl,
            $products
        );
    }

    private function moveFirstPriceListPricesWithTempTable(
        CombinedPriceList $combinedPriceList,
        array &$priceListRelations,
        array $products
    ): void {
        $firstRelation = array_shift($priceListRelations);
        $this->getCombinedProductPriceRepository()->copyPricesByPriceListWithTempTable(
            $this->tempTableManipulator,
            $combinedPriceList,
            $firstRelation->getPriceList(),
            $firstRelation->isMergeAllowed(),
            $products
        );
    }

    private function moveFirstPriceListPrices(
        CombinedPriceList $combinedPriceList,
        array &$priceListRelations,
        array $products
    ): void {
        $firstRelation = array_shift($priceListRelations);
        $this->getCombinedProductPriceRepository()->copyPricesByPriceList(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $firstRelation->getPriceList(),
            $firstRelation->isMergeAllowed(),
            $products
        );
    }

    private function canUseTempTable(CombinedPriceList $combinedPriceList): bool
    {
        if (!$this->getInsertSelectExecutor() instanceof ShardQueryExecutorNativeSqlInterface) {
            return false;
        }

        try {
            $this->tempTableManipulator->createTempTableForEntity(
                CombinedProductPrice::class,
                $combinedPriceList->getId()
            );

            return true;
        } catch (Exception $e) {
            // If exception occurs during temp table creation - it's not possible to use temp table optimization.
            return false;
        }
    }

    private function processPriceListsWithTempTable(
        CombinedPriceList $combinedPriceList,
        array $priceListRelations,
        array $products
    ): void {
        foreach ($priceListRelations as $priceListRelation) {
            $this->processRelationWithTempTable($combinedPriceList, $priceListRelation, $products);
        }

        // Copy prepared prices from temp to persistent CPL table and Drop temp table
        $this->tempTableManipulator->copyDataFromTemplateTableToEntityTable(
            CombinedProductPrice::class,
            $combinedPriceList->getId(),
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed',
                'originPriceId',
                'id',
            ]
        );

        $this->tempTableManipulator->dropTempTableForEntity(
            CombinedProductPrice::class,
            $combinedPriceList->getId()
        );
    }

    private function processRelationWithTempTable(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products
    ): void {
        $this->getCombinedProductPriceRepository()->insertPricesByPriceListWithTempTable(
            $this->tempTableManipulator,
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $priceListRelation->isMergeAllowed(),
            $products
        );
    }

    private function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products = []
    ): void {
        $this->getCombinedProductPriceRepository()->insertPricesByPriceList(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $priceListRelation->isMergeAllowed(),
            $products
        );
    }
}
