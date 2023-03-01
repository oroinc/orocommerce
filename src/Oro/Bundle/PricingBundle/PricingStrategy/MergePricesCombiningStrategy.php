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

    /**
     * {@inheritdoc}
     *
     * If there is at least one price list with merge disallowed in the fallback combined price lists chain
     * it is impossible to use this fallback combined price list because prices with `merge = false` when found
     * at the first time are moved to combined price lists and block further product`s prices processing, but when
     * `merge = false` price processed in the middle of the chain it is simply ignored. Cutting the chain with
     * `merge = false` price list will lead to a situation when prices with `merge = true` that follows `merge = false`
     * may be skipped compared to sequential price list processing.
     */
    protected function isFallbackMergeAllowed(array $relationsCollection): bool
    {
        return parent::isFallbackMergeAllowed($relationsCollection)
            && !$this->containMergeDisallowed($relationsCollection);
    }

    protected function getFallbackCombinedPriceList(CombinedPriceList $combinedPriceList): ?CombinedPriceList
    {
        return $this->getCombinedPriceListRelationsRepository()->findFallbackCplUsingMergeFlag($combinedPriceList);
    }

    protected function getPriceListRelationsNotIncludedInFallback(
        array $combinedPriceListRelation,
        array $fallbackCplRelations
    ): array {
        // return only a part of price lists chain that is not included in the fallback cpl price lists chain
        // Example CPL: 1t_3f_4t_6f, fallback CPL: 1t_3f. Return chain will consist of 4t_6f
        return array_splice($combinedPriceListRelation, 0, -\count($fallbackCplRelations));
    }

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

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return bool
     */
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

    /**
     * @param array|CombinedPriceListToPriceList[] $collection
     * @return bool
     */
    private function containMergeDisallowed(array $collection): bool
    {
        foreach ($collection as $item) {
            if (!$item->isMergeAllowed()) {
                return true;
            }
        }

        return false;
    }
}
