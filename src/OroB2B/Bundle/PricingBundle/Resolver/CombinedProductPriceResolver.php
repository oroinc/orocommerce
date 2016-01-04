<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceResolver
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
        //TODO: BB-1843
    }
}
