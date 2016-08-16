<?php

namespace OroB2B\Bundle\PricingBundle\Async\Message;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceRuleCalculateMessage
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
