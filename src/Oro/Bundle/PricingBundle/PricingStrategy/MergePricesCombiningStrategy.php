<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Platforms\MySqlPlatform;
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

    protected function getFallbackCombinedPriceList(CombinedPriceList $combinedPriceList): ?CombinedPriceList
    {
        return $this->getCombinedPriceListRelationsRepository()->findFallbackCplUsingMergeFlag($combinedPriceList);
    }

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

    protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceListRelations,
        array $products = [],
        ProgressBar $progressBar = null
    ) {
        if (count($priceListRelations) == 0) {
            return;
        }

        $progress = 0;
        if (count($priceListRelations) > 1 && $this->canUseTempTable($combinedPriceList)) {
            $this->moveFirstPriceListPricesWithTempTable(
                $combinedPriceList,
                $priceListRelations,
                $products,
                $progress,
                $progressBar
            );

            $this->processPriceListsWithTempTable(
                $combinedPriceList,
                $priceListRelations,
                $products,
                $progress,
                $progressBar
            );
        } else {
            $this->moveFirstPriceListPrices(
                $combinedPriceList,
                $priceListRelations,
                $products,
                $progress,
                $progressBar
            );

            foreach ($priceListRelations as $priceListRelation) {
                $this->moveProgress($progressBar, $progress, $priceListRelation);
                $this->processRelation($combinedPriceList, $priceListRelation, $products);
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

    private function moveFirstPriceListPricesWithTempTable(
        CombinedPriceList $combinedPriceList,
        array &$priceListRelations,
        array $products,
        int $progress,
        ?ProgressBar $progressBar
    ): void {
        $firstRelation = array_shift($priceListRelations);
        $this->moveProgress($progressBar, $progress, $firstRelation);
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
        if (!$this->getInsertSelectExecutor() instanceof ShardQueryExecutorNativeSqlInterface
            || $this->registry->getConnection()->getDatabasePlatform() instanceof MySqlPlatform
        ) {
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

    protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products = []
    ) {
        $this->getCombinedProductPriceRepository()->insertPricesByPriceList(
            $this->getInsertSelectExecutor(),
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $priceListRelation->isMergeAllowed(),
            $products
        );
    }
}
