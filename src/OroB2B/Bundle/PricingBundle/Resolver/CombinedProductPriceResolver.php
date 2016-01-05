<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
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
    protected $insertFromSelectExecutor;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(ManagerRegistry $registry, InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor)
    {
        $this->registry = $registry;
        $this->insertFromSelectExecutor = $insertFromSelectQueryExecutor;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     */
    public function combinePrices(CombinedPriceList $combinedPriceList)
    {
        $repo = $this->registry
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToPriceList');

        foreach ($repo->getPriceListsByCombined($combinedPriceList) as $combinedPriceListToPriceList) {
            $this->registry->getRepository('OroB2BPricingBundle:CombinedProductPrice')->insertPrices(
                $combinedPriceListToPriceList,
                $this->insertFromSelectExecutor
            );
        }
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     */
    public function updatePricesByProduct(CombinedPriceList $combinedPriceList, Product $product)
    {
        //TODO: BB-1843
    }
}
