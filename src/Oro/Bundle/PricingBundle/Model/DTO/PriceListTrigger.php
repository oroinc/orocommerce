<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\ProductBundle\Entity\Product;

class PriceListTrigger
{
    /**
     * @var array|Product[]
     */
    protected $products;

    /**
     * @param array|Product[] $products
     */
    public function __construct(array $products = [])
    {
        $this->products = $products;
    }

    /**
     * @return array {"<priceListId>" => ["<productId1>", "<productId2>", ...], ...}
     */
    public function getProducts()
    {
        return $this->products;
    }
}
