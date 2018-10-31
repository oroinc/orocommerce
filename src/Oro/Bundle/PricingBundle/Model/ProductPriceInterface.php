<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductPriceInterface
{
    /**
     * @return float
     */
    public function getQuantity();

    /**
     * @return Price
     */
    public function getPrice();

    /**
     * @return MeasureUnitInterface
     */
    public function getUnit();

    /**
     * @return Product
     */
    public function getProduct();
}
