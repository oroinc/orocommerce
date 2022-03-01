<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\DBAL\Driver\Exception;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorNativeSqlInterface;
use Oro\Bundle\PricingBundle\ORM\TempTableManipulatorInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Implements combining price strategy base on PriceList priority and additional flag "mergeAllowed"
 */
class MergePricesCombiningStrategy extends AbstractPriceCombiningStrategy
{
    const NAME = 'merge_by_priority';

    /**
     * @var TempTableManipulatorInterface
     */
    private $tempTableManipulator;

    public function setTempTableManipulator(TempTableManipulatorInterface $tempTableManipulator)
    {
        $this->tempTableManipulator = $tempTableManipulator;
    }

    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor)
    {
        if ($queryExecutor instanceof ShardQueryExecutorNativeSqlInterface) {
            $this->tempTableManipulator->setInsertSelectExecutor($queryExecutor);
        }

        parent::setInsertSelectExecutor($queryExecutor);
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
        array $products = [],
        ProgressBar $progressBar = null
    ) {
        if (count($priceListRelations) > 0) {
            $progress = 0;
            $this->moveFirstPriceListPrices(
                $combinedPriceList,
                $priceListRelations,
                $products,
                $progress,
                $progressBar
            );

            if ($this->canUseTempTable($combinedPriceList)) {
                $this->processPriceListsWithTempTable(
                    $combinedPriceList,
                    $priceListRelations,
                    $products,
                    $progress,
                    $progressBar
                );
            } else {
                foreach ($priceListRelations as $priceListRelation) {
                    $this->moveProgress($progressBar, $progress, $priceListRelation);
                    $this->processRelation($combinedPriceList, $priceListRelation, $products);
                }
            }
        }
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
        array $products = [],
        ProgressBar $progressBar = null
    ): void {
        $this->getCombinedProductPriceRepository()->insertPricesByCombinedPriceListIncludingProducts(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $fallbackCpl,
            $products
        );
    }

    private function moveFirstPriceListPrices(
        CombinedPriceList $combinedPriceList,
        array &$priceListRelations,
        array $products,
        int $progress,
        ?ProgressBar $progressBar
    ): void {
        $firstRelation = array_shift($priceListRelations);
        $this->moveProgress($progressBar, $progress, $firstRelation);
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
        array $products,
        int &$progress,
        ?ProgressBar $progressBar
    ): void {
        foreach ($priceListRelations as $priceListRelation) {
            $this->moveProgress($progressBar, $progress, $priceListRelation);
            $this->processRelationWithTempTable($combinedPriceList, $priceListRelation, $products);
        }

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

    /**
     * {@inheritdoc}
     */
    protected function processRelation(
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
