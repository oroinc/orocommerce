<?php

namespace Oro\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceResolver
{
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
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(ManagerRegistry $registry, InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor)
    {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     */
    public function combinePrices(CombinedPriceList $combinedPriceList, Product $product = null)
    {
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
        $this->getManager()->getRepository('OroPricingBundle:MinimalProductPrice')->updateMinimalPrices(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $product
        );
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
}
