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
     * @var array|Product[]
     */
    protected $products;

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function __construct(PriceList $priceList, array $products = [])
    {
        $this->priceList = $priceList;
        $this->products = $products;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return array|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }
}
