<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceListTrigger
{
    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var Product|null
     */
    protected $product;

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function __construct(PriceList $priceList, Product $product = null)
    {
        $this->priceList = $priceList;
        $this->product = $product;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->product;
    }
}
