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

            if ($this->canUseTempTable($priceLists, $combinedPriceList)) {
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
     * @param array|CombinedPriceListToPriceList[] $priceLists
     * @param CombinedPriceList $combinedPriceList
     * @return bool
     */
    private function canUseTempTable(array $priceLists, CombinedPriceList $combinedPriceList): bool
    {
        if (!$this->insertFromSelectQueryExecutor instanceof ShardQueryExecutorNativeSqlInterface) {
            return false;
        }

        $mayUseTempTable = false;
        /** @var CombinedPriceListToPriceList $priceListRelation */
        foreach ($priceLists as $priceListRelation) {
            if ($priceListRelation->isMergeAllowed()) {
                $mayUseTempTable = true;
                break;
            }
        }

        if ($mayUseTempTable) {
            try {
                $this->tempTableManipulator->createTempTableForEntity(
                    CombinedProductPrice::class,
                    $combinedPriceList->getId()
                );

                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
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

            if ($priceListRelation->isMergeAllowed()) {
                $this->processRelationWithTempTable($combinedPriceList, $priceListRelation, $products);
            } else {
                $this->processRelation($combinedPriceList, $priceListRelation, $products);
            }
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
