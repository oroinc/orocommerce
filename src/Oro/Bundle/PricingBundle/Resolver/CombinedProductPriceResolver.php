<?php

namespace Oro\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceResolver
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var CombinedPriceListToPriceListRepository
     */
    protected $combinedPriceListRelationsRepository;

    /**
     * @var CombinedProductPriceRepository
     */
    protected $combinedProductPriceRepository;
    /**
     * @var array
     */
    protected $builtList = [];

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param CombinedPriceListTriggerHandler $triggerHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     * @param int|null $startTimestamp
     */
    public function combinePrices(CombinedPriceList $combinedPriceList, Product $product = null, $startTimestamp = null)
    {
        if ($product === null
            && $startTimestamp !== null
            && !empty($this->builtList[$startTimestamp][$combinedPriceList->getId()])
        ) {
            //this CPL was recalculated at this go
            return;
        }
        $priceListsRelations = $this->getCombinedPriceListRelationsRepository()
            ->getPriceListRelations(
                $combinedPriceList,
                $product
            );

        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList, $product);
        foreach ($priceListsRelations as $priceListRelation) {
            $combinedPriceRepository->insertPricesByPriceList(
                $this->insertFromSelectQueryExecutor,
                $combinedPriceList,
                $priceListRelation->getPriceList(),
                $priceListRelation->isMergeAllowed(),
                $product
            );
        }
        if (!$product) {
            $combinedPriceList->setPricesCalculated(true);
            $this->getManager()->flush($combinedPriceList);
        }

        $this->triggerHandler->processByProduct($combinedPriceList, $product);
        $this->builtList[$startTimestamp][$combinedPriceList->getId()] = true;
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $className = 'OroPricingBundle:CombinedPriceList';
            $this->manager = $this->registry
                ->getManagerForClass($className);
        }

        return $this->manager;
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCombinedPriceListRelationsRepository()
    {
        if (!$this->combinedPriceListRelationsRepository) {
            $priceListRelationClassName = 'OroPricingBundle:CombinedPriceListToPriceList';
            $this->combinedPriceListRelationsRepository = $this->registry
                ->getManagerForClass($priceListRelationClassName)
                ->getRepository($priceListRelationClassName);
        }

        return $this->combinedPriceListRelationsRepository;
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getCombinedProductPriceRepository()
    {
        if (!$this->combinedProductPriceRepository) {
            $combinedPriceClassName = 'OroPricingBundle:CombinedProductPrice';
            $this->combinedProductPriceRepository = $this->registry
                ->getManagerForClass($combinedPriceClassName)
                ->getRepository($combinedPriceClassName);
        }

        return $this->combinedProductPriceRepository;
    }

    /**
     * @return $this
     */
    public function resetCache()
    {
        $this->builtList = [];

        return $this;
    }
}
