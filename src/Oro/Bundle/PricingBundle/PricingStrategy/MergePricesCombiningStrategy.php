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

    /**
     * {@inheritdoc}
     */
    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor)
    {
        if ($queryExecutor instanceof ShardQueryExecutorNativeSqlInterface) {
            $this->tempTableManipulator->setInsertSelectExecutor($queryExecutor);
        }

        parent::setInsertSelectExecutor($queryExecutor);
    }

    /**
     * {@inheritdoc}
     */
    protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        array $products = [],
        ProgressBar $progressBar = null
    ) {
        if (count($priceLists) > 0) {
            $progress = 0;
            $this->moveFirstPriceListPrices($combinedPriceList, $priceLists, $products, $progress, $progressBar);

            if ($this->canUseTempTable($combinedPriceList)) {
                $this->processPriceListsWithTempTable(
                    $combinedPriceList,
                    $priceLists,
                    $products,
                    $progress,
                    $progressBar
                );
            } else {
                foreach ($priceLists as $priceListRelation) {
                    $this->moveProgress($progressBar, $progress, $priceListRelation);
                    $this->processRelation($combinedPriceList, $priceListRelation, $products);
                }
            }
        }
    }

    private function moveFirstPriceListPrices(
        CombinedPriceList $combinedPriceList,
        array &$priceLists,
        array $products,
        int $progress,
        ?ProgressBar $progressBar
    ): void {
        $firstRelation = array_shift($priceLists);
        $this->moveProgress($progressBar, $progress, $firstRelation);
        $this->getCombinedProductPriceRepository()->copyPricesByPriceList(
            $this->insertFromSelectQueryExecutor,
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
        if (!$this->insertFromSelectQueryExecutor instanceof ShardQueryExecutorNativeSqlInterface) {
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
        array $priceLists,
        array $products,
        int &$progress,
        ?ProgressBar $progressBar
    ): void {
        foreach ($priceLists as $priceListRelation) {
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
    ) {
        $this->getCombinedProductPriceRepository()->insertPricesByPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $priceListRelation->isMergeAllowed(),
            $products
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $relatedCombinedPriceList
    ) {
        $this->getCombinedProductPriceRepository()->insertPricesByCombinedPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $relatedCombinedPriceList
        );
    }
}
