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
     * @param CombinedPriceList $combinedPriceList
     */
    public function combinePrices(CombinedPriceList $combinedPriceList)
    {

    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     */
    public function updatePricesByProduct(CombinedPriceList $combinedPriceList, Product $product)
    {

    }
}
