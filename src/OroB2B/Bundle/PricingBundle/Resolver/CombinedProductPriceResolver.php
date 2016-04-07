<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceResolver
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCombinedPriceListRelationsRepository()
    {
        if (!$this->combinedPriceListRelationsRepository) {
            $priceListRelationClassName = 'OroB2BPricingBundle:CombinedPriceListToPriceList';
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
            $combinedPriceClassName = 'OroB2BPricingBundle:CombinedProductPrice';
            $this->combinedProductPriceRepository = $this->registry
                ->getManagerForClass($combinedPriceClassName)
                ->getRepository($combinedPriceClassName);
        }

        return $this->combinedProductPriceRepository;
    }
}
