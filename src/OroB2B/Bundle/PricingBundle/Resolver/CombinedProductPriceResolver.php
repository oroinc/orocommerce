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
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(ManagerRegistry $registry, InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor)
    {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
    }

    /**
     * @param CombinedPriceList $priceList
     */
    public function combinePrices(CombinedPriceList $priceList)
    {
        //TODO: BB-1842
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     */
    public function updatePricesByProduct(CombinedPriceList $combinedPriceList, Product $product)
    {
        $priceListRelationClassName = 'OroB2BPricingBundle:CombinedPriceListToPriceList';
        $combinedPriceClassName = 'OroB2BPricingBundle:CombinedProductPrice';
        /**
         * @var $priceListRelationRepository CombinedPriceListToPriceListRepository
         */
        $priceListRelationRepository = $this->registry->getManagerForClass($priceListRelationClassName)
            ->getRepository($priceListRelationClassName);
        /**
         * @var $combinedPriceRepository CombinedProductPriceRepository
         */
        $combinedPriceRepository = $this->registry->getManagerForClass($combinedPriceClassName)
            ->getRepository($combinedPriceClassName);

        $priceListsRelations = $priceListRelationRepository->getPriceListsByCombinedAndProduct(
            $combinedPriceList,
            $product
        );

        $combinedPriceRepository->deletePricesByProduct($combinedPriceList, $product);
        foreach ($priceListsRelations as $priceListRelation) {
            $combinedPriceRepository->insertPricesByPriceListForProduct(
                $this->insertFromSelectQueryExecutor,
                $combinedPriceList,
                $priceListRelation->getPriceList(),
                $product,
                $priceListRelation->isMergeAllowed()
            );
        }
    }
}
